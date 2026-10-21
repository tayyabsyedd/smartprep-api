<?php

namespace App\Services;

use App\Models\EnrollmentRequest;
use App\Models\InstituteRoster;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Manual (admin) review of enrollment requests that did NOT auto-match
 * the institute roster — the BR-02 approval queue. Mirrors the semantics
 * of EnrollmentController::verify(): approving claims the roster row when
 * it is still unclaimed and flips the student to enrolled.
 */
class EnrollmentReviewService
{
    public function approve(EnrollmentRequest $request, User $admin, ?string $note = null): EnrollmentRequest
    {
        return DB::transaction(function () use ($request, $admin, $note) {
            $student = $request->user;

            $roster = InstituteRoster::where('enrollment_id', $request->enrollment_id)
                ->where('is_claimed', false)
                ->first();

            if ($roster) {
                $roster->update(['is_claimed' => true, 'claimed_by' => $student->id]);
            }

            $student->update([
                'is_enrolled' => true,
                'enrollment_id' => $request->enrollment_id,
            ]);

            $request->update([
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'note' => $note ?? 'Approved by admin after manual review.',
            ]);

            return $request;
        });
    }

    public function reject(EnrollmentRequest $request, User $admin, ?string $note = null): EnrollmentRequest
    {
        $request->update([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'note' => $note ?? 'Rejected by admin after manual review.',
        ]);

        return $request;
    }
}
