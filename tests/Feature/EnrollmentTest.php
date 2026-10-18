<?php

namespace Tests\Feature;

use App\Models\EnrollmentRequest;
use App\Models\InstituteRoster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function headers(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('mobile')->plainTextToken];
    }

    public function test_roster_match_auto_enrolls_instantly(): void
    {
        InstituteRoster::create(['enrollment_id' => 'ROLL-101', 'student_name' => 'Ali Raza']);
        $user = User::factory()->create(['is_enrolled' => false]);

        $response = $this->postJson('/api/enrollment/verify', ['roll_id' => 'ROLL-101'], $this->headers($user));

        $response->assertOk()->assertJsonPath('is_enrolled', true);

        $user->refresh();
        $this->assertTrue((bool) $user->is_enrolled);
        $this->assertSame('ROLL-101', $user->enrollment_id);
        $this->assertDatabaseHas('institute_roster', [
            'enrollment_id' => 'ROLL-101',
            'is_claimed' => true,
            'claimed_by' => $user->id,
        ]);
        $this->assertDatabaseHas('enrollment_requests', [
            'user_id' => $user->id,
            'enrollment_id' => 'ROLL-101',
            'status' => 'approved',
        ]);
    }

    public function test_claimed_roll_id_falls_back_to_manual_review(): void
    {
        $other = User::factory()->create();
        InstituteRoster::create(['enrollment_id' => 'ROLL-102', 'is_claimed' => true, 'claimed_by' => $other->id]);

        $user = User::factory()->create(['is_enrolled' => false]);

        $this->postJson('/api/enrollment/verify', ['roll_id' => 'ROLL-102'], $this->headers($user))
            ->assertStatus(202)
            ->assertJsonPath('is_enrolled', false);

        $this->assertDatabaseHas('enrollment_requests', [
            'user_id' => $user->id,
            'enrollment_id' => 'ROLL-102',
            'status' => 'pending',
        ]);
        $this->assertFalse((bool) $user->refresh()->is_enrolled);
    }

    public function test_unknown_roll_id_creates_pending_request(): void
    {
        $user = User::factory()->create(['is_enrolled' => false]);

        $this->postJson('/api/enrollment/verify', ['roll_id' => 'NOPE-999'], $this->headers($user))
            ->assertStatus(202);

        $this->assertDatabaseHas('enrollment_requests', [
            'user_id' => $user->id,
            'enrollment_id' => 'NOPE-999',
            'status' => 'pending',
        ]);
    }

    public function test_already_enrolled_user_is_rejected(): void
    {
        $user = User::factory()->create(['is_enrolled' => true, 'enrollment_id' => 'ROLL-100']);

        $this->postJson('/api/enrollment/verify', ['roll_id' => 'ROLL-101'], $this->headers($user))
            ->assertUnprocessable();
    }

    public function test_duplicate_pending_request_is_not_created_twice(): void
    {
        $user = User::factory()->create(['is_enrolled' => false]);
        $headers = $this->headers($user);

        $this->postJson('/api/enrollment/verify', ['roll_id' => 'NOPE-999'], $headers)->assertStatus(202);
        $this->postJson('/api/enrollment/verify', ['roll_id' => 'NOPE-999'], $headers)->assertStatus(202);

        $this->assertSame(1, EnrollmentRequest::where('user_id', $user->id)->count());
    }

    public function test_roll_id_is_required(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/enrollment/verify', [], $this->headers($user))
            ->assertUnprocessable();
    }
}
