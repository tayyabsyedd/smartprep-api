<?php

namespace App\Services\Payments\Drivers;

use App\Models\Purchase;
use App\Services\Payments\Contracts\PaymentDriver;
use RuntimeException;

/**
 * JazzCash (Mobilink Microfinance Bank) gateway driver.
 *
 * STUB — wire up once the client provides the registered business
 * merchant account credentials. Fill JAZZCASH_* in .env (never commit them).
 */
class JazzCashDriver implements PaymentDriver
{
    public function key(): string
    {
        return 'jazzcash';
    }

    public function initiate(Purchase $purchase): array
    {
        $this->ensureConfigured();

        // TODO (needs merchant credentials):
        // 1. Generate our own transaction reference, store it in
        //    $purchase->gateway_reference BEFORE calling the gateway,
        //    so the webhook can match the callback to this purchase.
        // 2. POST to the JazzCash transaction API with merchant ID,
        //    password, integrity salt and return URL from config/payments.php.
        // 3. Return ['redirect_url' => $gatewayUrl] for the app to open.
        throw new RuntimeException('JazzCash driver not configured yet — merchant credentials pending from client.');
    }

    public function verifyWebhook(array $payload, array $headers = []): array
    {
        $this->ensureConfigured();

        // TODO: validate pp_SecureHash against the integrity salt, then map
        // pp_ResponseCode '000' => completed, anything else => failed.
        // return ['status' => ..., 'gateway_reference' => $payload['pp_TxnRefNo'] ?? null, 'raw' => $payload];
        throw new RuntimeException('JazzCash driver not configured yet — merchant credentials pending from client.');
    }

    protected function ensureConfigured(): void
    {
        if (! config('payments.jazzcash.merchant_id')) {
            throw new RuntimeException('JazzCash merchant credentials are not configured.');
        }
    }
}
