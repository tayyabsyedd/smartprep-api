<?php

namespace App\Services;

use App\Models\Test;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Decides whether a user may attempt a test.
 *
 * Access paths (in order):
 *  - 'free'        — the test is marked is_free (never consumes quota)
 *  - 'entitlement' — the user holds a live entitlement (purchase/grant)
 *  - 'quota'       — the user still has free attempts left in the module
 *
 * Only quota-path attempts consume the free allowance (tracked on
 * test_attempts.used_free_quota). Purchased attempts and free tests don't.
 */
class AccessService
{
    public function accessVia(User $user, Test $test): ?string
    {
        if ($test->is_free) {
            return 'free';
        }

        if ($user->canAccess(Test::class, $test->id)) {
            return 'entitlement';
        }

        if ($user->freeTestsRemaining($test->module) > 0) {
            return 'quota';
        }

        return null;
    }

    /**
     * Returns the access path, or throws the standard 403 paywall payload.
     */
    public function ensureCanAttempt(User $user, Test $test): string
    {
        $via = $this->accessVia($user, $test);

        if ($via === null) {
            throw new HttpResponseException(response()->json([
                'error' => 'quota_exceeded',
                'message' => 'Purchase required to attempt this test.',
                'test_id' => $test->id,
            ], 403));
        }

        return $via;
    }
}
