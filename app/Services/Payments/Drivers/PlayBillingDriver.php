<?php

namespace App\Services\Payments\Drivers;

use App\Models\Purchase;
use App\Services\Payments\Contracts\PaymentDriver;
use RuntimeException;

/**
 * Google Play Billing verification driver.
 *
 * STUB — used post-beta (M4) when the app is on a Play Console track.
 * Verification happens server-side via the Google Play Developer API
 * (purchases.products.get) using a service-account key.
 */
class PlayBillingDriver implements PaymentDriver
{
    public function key(): string
    {
        return 'play_billing';
    }

    public function initiate(Purchase $purchase): array
    {
        // Play Billing is initiated on-device via the Play Billing Library;
        // the server only verifies the purchase token afterwards.
        throw new RuntimeException('Play Billing is initiated in-app; use verifyWebhook with the purchase token.');
    }

    public function verifyWebhook(array $payload, array $headers = []): array
    {
        $this->ensureConfigured();

        // TODO: call the Play Developer API with the service-account JSON
        // from config('payments.play_billing.service_account_json'),
        // verify $payload['purchase_token'] for the package name, and map
        // purchaseState (0 = purchased) to completed/failed.
        // return ['status' => ..., 'gateway_reference' => $payload['order_id'] ?? null, 'raw' => $payload];
        throw new RuntimeException('Play Billing driver not configured yet — post-beta (M4).');
    }

    protected function ensureConfigured(): void
    {
        if (! config('payments.play_billing.service_account_json')) {
            throw new RuntimeException('Play Billing service account is not configured.');
        }
    }
}
