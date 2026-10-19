<?php

namespace App\Services\Payments\Drivers;

use App\Models\Purchase;
use App\Services\Payments\Contracts\PaymentDriver;
use App\Services\Payments\PaymentManager;
use BadMethodCallException;
use RuntimeException;

/**
 * Development/test driver.
 *
 * Completes the purchase instantly — no gateway is called, no money moves,
 * and an Entitlement is granted through PaymentManager (so quotas unlock
 * exactly like a real payment). Refuses to run unless
 * payments.mock.enabled is true (defaults to true only in local env).
 */
class MockPaymentDriver implements PaymentDriver
{
    public function __construct(protected PaymentManager $payments) {}

    public function key(): string
    {
        return 'mock';
    }

    public function initiate(Purchase $purchase): array
    {
        if (! config('payments.mock.enabled')) {
            throw new RuntimeException('Mock payments are disabled in this environment.');
        }

        $reference = 'MOCK-'.$purchase->id.'-'.now()->format('YmdHis');

        $this->payments->complete($purchase, $reference, [
            'driver' => 'mock',
            'note' => 'Instant test payment — no money moved (development only).',
        ]);

        return [
            'purchase_id' => $purchase->id,
            'status' => 'completed',
            'gateway_reference' => $reference,
            'payment_data' => ['message' => 'Test payment completed instantly.'],
        ];
    }

    public function verifyWebhook(array $payload, array $headers = []): array
    {
        throw new BadMethodCallException('Mock driver completes at initiation; there is no webhook.');
    }
}
