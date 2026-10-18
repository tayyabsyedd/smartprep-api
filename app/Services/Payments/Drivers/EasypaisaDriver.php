<?php

namespace App\Services\Payments\Drivers;

use App\Models\Purchase;
use App\Services\Payments\Contracts\PaymentDriver;
use RuntimeException;

/**
 * EasyPaisa (Telenor Microfinance Bank) gateway driver.
 *
 * STUB — wire up once the client provides the registered business
 * merchant account credentials. Fill EASYPAISA_* in .env (never commit them).
 */
class EasypaisaDriver implements PaymentDriver
{
    public function key(): string
    {
        return 'easypaisa';
    }

    public function initiate(Purchase $purchase): array
    {
        $this->ensureConfigured();

        // TODO (needs merchant credentials):
        // 1. Generate our own order reference, store it in
        //    $purchase->gateway_reference BEFORE calling the gateway.
        // 2. Call the EasyPaisa transaction API with store ID + hash key
        //    from config/payments.php.
        // 3. Return ['redirect_url' => $gatewayUrl] (or deeplink) for the app.
        throw new RuntimeException('EasyPaisa driver not configured yet — merchant credentials pending from client.');
    }

    public function verifyWebhook(array $payload, array $headers = []): array
    {
        $this->ensureConfigured();

        // TODO: validate the callback hash using the store hash key, map the
        // gateway status to completed/failed.
        // return ['status' => ..., 'gateway_reference' => ..., 'raw' => $payload];
        throw new RuntimeException('EasyPaisa driver not configured yet — merchant credentials pending from client.');
    }

    protected function ensureConfigured(): void
    {
        if (! config('payments.easypaisa.store_id')) {
            throw new RuntimeException('EasyPaisa merchant credentials are not configured.');
        }
    }
}
