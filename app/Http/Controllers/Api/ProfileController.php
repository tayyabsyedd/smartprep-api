<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * GET /api/profile/test-history (BR-08)
     */
    public function testHistory(Request $request)
    {
        $user = $request->user();

        $attempts = $user->testAttempts()
            ->where('status', 'submitted')
            ->with('test.module')
            ->latest('submitted_at')
            ->get();

        return response()->json([
            'total_tests_taken' => $attempts->count(),
            'cumulative_practice_seconds' => (int) $attempts->sum('time_spent_seconds'),
            'attempts' => $attempts->map(fn ($attempt) => [
                'attempt_id' => $attempt->id,
                'test_id' => $attempt->test_id,
                'test_title' => $attempt->test->title,
                'module' => $attempt->test->module->name,
                'score' => $attempt->score,
                'total_marks' => $attempt->total_marks,
                'percentage' => $attempt->total_marks > 0
                    ? round(($attempt->score / $attempt->total_marks) * 100, 1)
                    : 0,
                'time_spent_seconds' => $attempt->time_spent_seconds,
                'app_switches' => $attempt->app_switches,
                'submitted_at' => $attempt->submitted_at?->toISOString(),
            ])->values(),
        ]);
    }
}
