<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    /**
     * Gateway callback endpoint.
     * POST /api/webhooks/payments/{driver}
     *
     * Each driver validates its own signature inside verifyWebhook().
     * Transitions are idempotent — a duplicate callback is harmless.
     */
    public function handle(Request $request, string $driver, PaymentManager $payments)
    {
        $paymentDriver = $payments->driver($driver);

        $result = $paymentDriver->verifyWebhook(
            $request->all(),
            $request->headers->all()
        );

        $purchase = Purchase::where('gateway_reference', $result['gateway_reference'] ?? null)->first();

        if (! $purchase) {
            return response()->json(['message' => 'No purchase matches this reference.'], 404);
        }

        if (($result['status'] ?? null) === 'completed') {
            $payments->complete($purchase, $result['gateway_reference'], $result['raw']);
        } else {
            $payments->fail($purchase, $result['raw'] ?? []);
        }

        return response()->json(['message' => 'Webhook processed.']);
    }
}
