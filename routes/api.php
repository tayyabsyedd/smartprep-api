<?php

use App\Http\Controllers\Api\PaymentWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Payment gateway callbacks. Each driver validates its own signature.
// Bank transfers have no callback — admin approval calls PaymentManager directly.
Route::post('/webhooks/payments/{driver}', [PaymentWebhookController::class, 'handle'])
    ->whereIn('driver', ['jazzcash', 'easypaisa', 'play_billing']);
