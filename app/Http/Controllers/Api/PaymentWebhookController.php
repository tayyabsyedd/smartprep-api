<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class PaymentWebhookController extends Controller
{
    /**
     * Gateway callback endpoint.
     * POST /api/webhooks/payments/{driver}
     * POST /api/webhooks/jazzcash   (fixed URL for gateway configuration)
     * POST /api/webhooks/easypaisa  (fixed URL for gateway configuration)
     *
     * Each driver validates its own signature inside verifyWebhook() and
     * throws on anything untrusted — we fail closed with a 400.
     * Transitions are idempotent — a duplicate callback is harmless.
     */
    public function handle(Request $request, string $driver, PaymentManager $payments)
    {
        try {
            $result = $payments->driver($driver)->verifyWebhook(
                $request->all(),
                $request->headers->all()
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => 'Unknown payment driver.'], 404);
        } catch (RuntimeException $e) {
            return response()->json(['message' => 'Webhook rejected: '.$e->getMessage()], 400);
        }

        $purchase = Purchase::where('gateway_reference', $result['gateway_reference'] ?? null)->first();

        if (! $purchase) {
            return response()->json(['message' => 'No purchase matches this reference.'], 404);
        }

        // The callback must belong to this driver — no cross-driver confusion.
        if ($purchase->payment_method !== $driver) {
            return response()->json(['message' => 'Reference does not belong to this payment method.'], 400);
        }

        if (($result['status'] ?? null) === 'completed') {
            $payments->complete($purchase, $result['gateway_reference'], $result['raw']);
        } else {
            $payments->fail($purchase, $result['raw'] ?? []);
        }

        return response()->json(['message' => 'Webhook processed.']);
    }
}
