<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->post('/enrollment/verify', [EnrollmentController::class, 'verify']);

// Payment gateway callbacks. Each driver validates its own signature.
// Bank transfers have no callback — admin approval calls PaymentManager directly.
Route::post('/webhooks/payments/{driver}', [PaymentWebhookController::class, 'handle'])
    ->whereIn('driver', ['jazzcash', 'easypaisa', 'play_billing']);
