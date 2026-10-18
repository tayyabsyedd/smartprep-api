<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\VerifyEnrollmentRequest;
use App\Models\EnrollmentRequest;
use App\Models\InstituteRoster;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    /**
     * POST /api/enrollment/verify (auth required)
     * Body: { "roll_id": "ROLL-101" }
     *
     * Roster match (unclaimed) -> instant enrollment.
     * No match (or already claimed) -> pending request for admin review.
     */
    public function verify(VerifyEnrollmentRequest $request)
    {
        $user = $request->user();
        $rollId = trim($request->roll_id);

        if ($user->is_enrolled) {
            return response()->json(['message' => 'You are already enrolled.'], 422);
        }

        // Don't stack duplicate pending requests for the same roll ID.
        $existing = EnrollmentRequest::where('user_id', $user->id)
            ->where('enrollment_id', $rollId)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Your enrollment request is already under review.',
                'request' => $existing,
            ], 202);
        }

        $roster = InstituteRoster::where('enrollment_id', $rollId)
            ->where('is_claimed', false)
            ->first();

        if ($roster) {
            DB::transaction(function () use ($user, $roster, $rollId) {
                $roster->update(['is_claimed' => true, 'claimed_by' => $user->id]);

                $user->update(['is_enrolled' => true, 'enrollment_id' => $rollId]);

                EnrollmentRequest::create([
                    'user_id' => $user->id,
                    'enrollment_id' => $rollId,
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'note' => 'Auto-approved: matched institute roster.',
                ]);
            });

            return response()->json([
                'message' => 'Enrollment verified successfully.',
                'is_enrolled' => true,
            ]);
        }

        $enrollmentRequest = EnrollmentRequest::create([
            'user_id' => $user->id,
            'enrollment_id' => $rollId,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Roll ID not found in the institute roster. Your request has been submitted for manual review.',
            'is_enrolled' => false,
            'request' => $enrollmentRequest,
        ], 202);
    }
}
