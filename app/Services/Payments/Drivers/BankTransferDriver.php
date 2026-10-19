<?php

namespace App\Services\Payments\Drivers;

use App\Models\Purchase;
use App\Services\Payments\Contracts\PaymentDriver;
use BadMethodCallException;

/**
 * Manual bank transfer flow (BR-26 fallback).
 *
 * No gateway is called. The app shows the institute's bank details,
 * the student transfers the amount and uploads a receipt photo
 * (stored in purchases.proof_image_path). An admin verifies the proof
 * in the admin panel, which calls PaymentManager::complete().
 */
class BankTransferDriver implements PaymentDriver
{
    public function key(): string
    {
        return 'bank_transfer';
    }

    public function initiate(Purchase $purchase): array
    {
        return [
            'purchase_id' => $purchase->id,
            'status' => $purchase->status,
            'payment_data' => [
                'bank' => config('payments.bank.bank_name'),
                'account_title' => config('payments.bank.account_title'),
                'account_number' => config('payments.bank.account_number'),
                'iban' => config('payments.bank.iban'),
                'instructions' => 'Transfer the exact amount to the account above, then upload your payment receipt in the app. An admin will verify it and grant access.',
            ],
        ];
    }

    public function verifyWebhook(array $payload, array $headers = []): array
    {
        throw new BadMethodCallException('Bank transfers are approved manually; there is no webhook to verify.');
    }
}
