<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Test;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Checkout endpoints (M4).
 *
 * POST   /api/purchases/initiate          — start a purchase, hand the client
 *                                           the gateway payload / bank details.
 * POST   /api/purchases/{purchase}/upload-slip — bank-transfer receipt upload.
 * GET    /api/purchases/my-purchases      — the student's receipt history (BR-20).
 */
class PurchaseController extends Controller
{
    public function initiate(Request $request, PaymentManager $payments)
    {
        $data = $request->validate([
            'purchasable_type' => ['required', Rule::in(['test'])],
            'purchasable_id' => ['required', 'integer'],
            'payment_method' => ['required', Rule::in(['mock', 'jazzcash', 'easypaisa', 'bank_transfer'])],
            'phone_number' => [
                'nullable', 'string', 'max:20',
                Rule::requiredIf(fn () => in_array($request->input('payment_method'), ['jazzcash', 'easypaisa'])),
            ],
        ]);

        if ($data['payment_method'] === 'mock' && ! config('payments.mock.enabled')) {
            return response()->json(['message' => 'Mock payments are disabled in this environment.'], 422);
        }

        $test = Test::find($data['purchasable_id']);

        if (! $test) {
            return response()->json(['message' => 'Test not found.'], 404);
        }

        if ($test->is_free || $test->price === null) {
            return response()->json(['message' => 'This test is free and does not need a purchase.'], 422);
        }

        $purchase = Purchase::create([
            'user_id' => $request->user()->id,
            'purchasable_type' => Test::class,
            'purchasable_id' => $test->id,
            'amount' => $test->price,
            'currency' => 'PKR',
            'payment_method' => $data['payment_method'],
            'status' => $data['payment_method'] === 'bank_transfer' ? 'pending_verification' : 'pending',
            // Initiation context for drivers (e.g. the wallet phone number).
            'gateway_response' => array_filter(['phone_number' => $data['phone_number'] ?? null]),
        ]);

        try {
            $result = $payments->driver($data['payment_method'])->initiate($purchase);
        } catch (RuntimeException $e) {
            // Driver refused (e.g. gateway credentials still pending from the
            // client). The pending purchase is kept so it can be retried;
            // the client shows the reason instead of a blank 500.
            return response()->json([
                'message' => $e->getMessage(),
                'purchase_id' => $purchase->id,
                'status' => $purchase->status,
            ], 422);
        }

        return response()->json([
            'purchase_id' => $purchase->id,
            'status' => $purchase->fresh()->status,
            'redirect_url' => $result['redirect_url'] ?? null,
            'payment_data' => $result['payment_data'] ?? null,
        ], 201);
    }

    public function uploadSlip(Request $request, Purchase $purchase)
    {
        if ($purchase->user_id !== $request->user()->id) {
            abort(403, 'This purchase does not belong to you.');
        }

        if (! in_array($purchase->status, ['pending', 'pending_verification'])) {
            return response()->json(
                ['message' => "This purchase is already {$purchase->status}; no receipt is needed."],
                422
            );
        }

        $request->validate([
            'slip' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        // Laravel 11+: the local disk root is storage/app/private.
        $path = $request->file('slip')->store('payment_slips', 'local');
        $purchase->update(['proof_image_path' => $path]);

        return response()->json([
            'message' => 'Receipt uploaded. An admin will verify your payment and unlock your purchase.',
            'purchase' => $this->summarize($purchase->fresh()),
        ]);
    }

    public function myPurchases(Request $request)
    {
        $purchases = Purchase::where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Purchase $purchase) => $this->summarize($purchase));

        return response()->json(['purchases' => $purchases]);
    }

    protected function summarize(Purchase $purchase): array
    {
        $title = null;
        $type = $purchase->purchasable_type;

        if ($type === Test::class) {
            $type = 'test';
            $title = Test::whereKey($purchase->purchasable_id)->value('title');
        }

        return [
            'id' => $purchase->id,
            'item_title' => $title,
            'purchasable_type' => $type,
            'purchasable_id' => $purchase->purchasable_id,
            'amount' => $purchase->amount,
            'currency' => $purchase->currency,
            'payment_method' => $purchase->payment_method,
            'status' => $purchase->status,
            'purchased_at' => $purchase->purchased_at?->toIso8601String(),
            'created_at' => $purchase->created_at->toIso8601String(),
        ];
    }
}
