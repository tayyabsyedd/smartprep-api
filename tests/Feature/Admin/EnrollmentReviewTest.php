<?php

namespace Tests\Feature\Admin;

use App\Models\EnrollmentRequest;
use App\Models\InstituteRoster;
use App\Models\User;
use App\Services\EnrollmentReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private EnrollmentReviewService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->service = app(EnrollmentReviewService::class);
    }

    public function test_approve_enrolls_student_and_claims_roster_row(): void
    {
        $student = User::factory()->create(['is_enrolled' => false]);
        $roster = InstituteRoster::create([
            'enrollment_id' => 'ROLL-900',
            'student_name' => $student->name,
            'is_claimed' => false,
        ]);
        $request = EnrollmentRequest::create([
            'user_id' => $student->id,
            'enrollment_id' => 'ROLL-900',
            'status' => 'pending',
        ]);

        $this->service->approve($request, $this->admin);

        $this->assertTrue($student->fresh()->is_enrolled);
        $this->assertSame('ROLL-900', $student->fresh()->enrollment_id);
        $this->assertTrue($roster->fresh()->is_claimed);
        $this->assertSame($student->id, $roster->fresh()->claimed_by);
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($this->admin->id, $request->fresh()->reviewed_by);
        $this->assertNotNull($request->fresh()->reviewed_at);
    }

    public function test_approve_without_roster_row_still_enrolls_student(): void
    {
        $student = User::factory()->create(['is_enrolled' => false]);
        $request = EnrollmentRequest::create([
            'user_id' => $student->id,
            'enrollment_id' => 'ROLL-UNKNOWN',
            'status' => 'pending',
        ]);

        $this->service->approve($request, $this->admin);

        $this->assertTrue($student->fresh()->is_enrolled);
        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_approve_does_not_steal_a_roster_row_claimed_by_another_student(): void
    {
        $other = User::factory()->create(['is_enrolled' => true]);
        $student = User::factory()->create(['is_enrolled' => false]);
        $roster = InstituteRoster::create([
            'enrollment_id' => 'ROLL-901',
            'student_name' => $other->name,
            'is_claimed' => true,
            'claimed_by' => $other->id,
        ]);
        $request = EnrollmentRequest::create([
            'user_id' => $student->id,
            'enrollment_id' => 'ROLL-901',
            'status' => 'pending',
        ]);

        $this->service->approve($request, $this->admin);

        $this->assertTrue($student->fresh()->is_enrolled);
        $this->assertSame($other->id, $roster->fresh()->claimed_by);
    }

    public function test_reject_keeps_student_unenrolled_and_marks_request(): void
    {
        $student = User::factory()->create(['is_enrolled' => false]);
        $request = EnrollmentRequest::create([
            'user_id' => $student->id,
            'enrollment_id' => 'ROLL-902',
            'status' => 'pending',
        ]);

        $this->service->reject($request, $this->admin, 'Roll ID does not match institute records.');

        $this->assertFalse($student->fresh()->is_enrolled);
        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame($this->admin->id, $request->fresh()->reviewed_by);
        $this->assertSame('Roll ID does not match institute records.', $request->fresh()->note);
    }
}
