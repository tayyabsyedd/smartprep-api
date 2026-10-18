<?php

namespace App\Services\Payments\Contracts;

use App\Models\Purchase;

interface PaymentDriver
{
    /**
     * Driver key — must match a value of purchases.payment_method.
     */
    public function key(): string;

    /**
     * Start a payment for a pending purchase.
     *
     * @return array Data the client needs to complete payment, e.g.
     *   ['redirect_url' => ...] for gateway-hosted flows, or
     *   ['bank' => ..., 'account_title' => ..., 'account_number' => ..., 'instructions' => ...]
     *   for manual bank transfer.
     */
    public function initiate(Purchase $purchase): array;

    /**
     * Verify an incoming gateway webhook/callback payload.
     * Implementations MUST validate the gateway signature/hash
     * before trusting anything in the payload.
     *
     * @return array{status: 'completed'|'failed', gateway_reference: ?string, raw: array}
     */
    public function verifyWebhook(array $payload, array $headers = []): array;
}
