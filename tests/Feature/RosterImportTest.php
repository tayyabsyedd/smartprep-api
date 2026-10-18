<?php

namespace Tests\Feature;

use App\Models\InstituteRoster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RosterImportTest extends TestCase
{
    use RefreshDatabase;

    protected function adminHeaders(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);

        return ['Authorization' => 'Bearer '.$admin->createToken('mobile')->plainTextToken];
    }

    protected function studentHeaders(): array
    {
        $student = User::factory()->create(['role' => 'student']);

        return ['Authorization' => 'Bearer '.$student->createToken('mobile')->plainTextToken];
    }

    protected function csvFile(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('roster.csv', $content);
    }

    public function test_admin_can_import_roster_csv(): void
    {
        $csv = "roll_id,student_name,program,batch\n"
            . "ROLL-201,Ali Raza,MDCAT,2026-A\n"
            . "ROLL-202,Sara Khan,ECAT,2026-B\n";

        $response = $this->postJson(
            '/api/admin/roster/import',
            ['file' => $this->csvFile($csv)],
            $this->adminHeaders()
        );

        $response->assertOk()->assertJson([
            'imported' => 2,
            'duplicates_skipped' => 0,
        ]);

        $this->assertDatabaseHas('institute_roster', [
            'enrollment_id' => 'ROLL-201',
            'student_name' => 'Ali Raza',
            'is_claimed' => false,
        ]);
    }

    public function test_existing_claims_survive_reimport(): void
    {
        $claimer = User::factory()->create();
        InstituteRoster::create([
            'enrollment_id' => 'ROLL-201',
            'student_name' => 'Old Name',
            'is_claimed' => true,
            'claimed_by' => $claimer->id,
        ]);

        $csv = "roll_id,student_name\n"
            . "ROLL-201,New Name\n"
            . "ROLL-202,Sara Khan\n";

        $response = $this->postJson(
            '/api/admin/roster/import',
            ['file' => $this->csvFile($csv)],
            $this->adminHeaders()
        );

        $response->assertOk()->assertJson([
            'imported' => 1,
            'duplicates_skipped' => 1,
        ]);

        // Name refreshed, but the claim is untouched.
        $this->assertDatabaseHas('institute_roster', [
            'enrollment_id' => 'ROLL-201',
            'student_name' => 'New Name',
            'is_claimed' => true,
            'claimed_by' => $claimer->id,
        ]);
    }

    public function test_duplicate_rows_inside_file_collapse(): void
    {
        $csv = "roll_id,student_name\n"
            . "ROLL-201,First\n"
            . "ROLL-201,Second\n";

        $this->postJson(
            '/api/admin/roster/import',
            ['file' => $this->csvFile($csv)],
            $this->adminHeaders()
        )->assertOk()->assertJson(['imported' => 1, 'duplicates_skipped' => 0]);

        $this->assertSame(1, InstituteRoster::where('enrollment_id', 'ROLL-201')->count());
    }

    public function test_non_admin_is_forbidden(): void
    {
        $csv = "roll_id,student_name\nROLL-201,Ali\n";

        $this->postJson(
            '/api/admin/roster/import',
            ['file' => $this->csvFile($csv)],
            $this->studentHeaders()
        )->assertForbidden();
    }

    public function test_guest_is_unauthorized(): void
    {
        $csv = "roll_id,student_name\nROLL-201,Ali\n";

        $this->postJson('/api/admin/roster/import', ['file' => $this->csvFile($csv)])
            ->assertUnauthorized();
    }

    public function test_csv_without_roll_id_column_is_rejected(): void
    {
        $csv = "name,program\nAli,MDCAT\n";

        $this->postJson(
            '/api/admin/roster/import',
            ['file' => $this->csvFile($csv)],
            $this->adminHeaders()
        )->assertUnprocessable()->assertJsonPath('message', "CSV must contain a 'roll_id' column.");
    }

    public function test_empty_csv_is_rejected(): void
    {
        $csv = "roll_id,student_name\n";

        $this->postJson(
            '/api/admin/roster/import',
            ['file' => $this->csvFile($csv)],
            $this->adminHeaders()
        )->assertUnprocessable();
    }
}
