<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TestController;
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

Route::middleware('auth:sanctum')->prefix('tests')->group(function () {
    Route::get('/{test}', [TestController::class, 'show']);
    Route::post('/{test}/start', [TestController::class, 'start']);
});

Route::middleware('auth:sanctum')->prefix('test-attempts')->group(function () {
    Route::post('/{attempt}/answers', [TestController::class, 'saveAnswers']);
    Route::post('/{attempt}/submit', [TestController::class, 'submit']);
    Route::get('/{attempt}/results', [TestController::class, 'results']);
});

Route::middleware('auth:sanctum')->get('/profile/test-history', [ProfileController::class, 'testHistory']);

// Payment gateway callbacks. Each driver validates its own signature.
// Bank transfers have no callback — admin approval calls PaymentManager directly.
Route::post('/webhooks/payments/{driver}', [PaymentWebhookController::class, 'handle'])
    ->whereIn('driver', ['jazzcash', 'easypaisa', 'play_billing']);
