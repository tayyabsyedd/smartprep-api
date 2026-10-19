<?php

namespace App\Services\Payments\Drivers;

use App\Models\Purchase;
use App\Services\Payments\Contracts\PaymentDriver;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * JazzCash (Mobilink Microfinance Bank) gateway driver — sandbox-ready.
 *
 * Builds the real MWALLET (mobile-wallet) payload and computes pp_SecureHash
 * per the official spec: ASCII-sort every pp* field, join the values with
 * '&', prepend "<integrity-salt>&", then HMAC-SHA256 keyed with the salt.
 * Uses the sandbox form URL until JAZZCASH_SANDBOX=false.
 *
 * Throws until the client's registered business merchant credentials are
 * in .env (JAZZCASH_MERCHANT_ID / JAZZCASH_PASSWORD / JAZZCASH_INTEGRITY_SALT).
 */
class JazzCashDriver implements PaymentDriver
{
    public const SANDBOX_FORM_URL = 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/';
    public const PRODUCTION_FORM_URL = 'https://payments.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/';

    public function key(): string
    {
        return 'jazzcash';
    }

    public function initiate(Purchase $purchase): array
    {
        $cfg = $this->config();
        $this->ensureConfigured($cfg);

        // Unguessable reference: the IPN handler trusts it, so it must not
        // be enumerable.
        $reference = 'SP-'.$purchase->id.'-'.Str::upper(Str::random(10));
        $purchase->update(['gateway_reference' => $reference]);

        $context = $purchase->gateway_response ?? [];

        $payload = [
            'pp_Amount' => (string) (int) round($purchase->amount * 100), // paisa
            'pp_BillReference' => 'smartprep-'.$purchase->id,
            'pp_Description' => 'SmartPrep test unlock',
            'pp_Language' => 'EN',
            'pp_MerchantID' => $cfg['merchant_id'],
            'pp_Password' => $cfg['password'],
            'pp_ReturnURL' => $cfg['return_url'],
            'pp_TxnCurrency' => $purchase->currency,
            'pp_TxnDateTime' => now()->format('YmdHis'),
            'pp_TxnExpiryDateTime' => now()->addHour()->format('YmdHis'),
            'pp_TxnRefNo' => $reference,
            'pp_TxnType' => 'MWALLET',
            'pp_Version' => '2.0',
        ];

        if (! empty($context['phone_number'])) {
            $payload['pp_MobileNumber'] = $context['phone_number'];
        }

        if (! empty($purchase->user_id)) {
            $payload['ppmpf_1'] = (string) $purchase->user_id;
        }

        $payload['pp_SecureHash'] = self::computeSecureHash($payload, $cfg['integrity_salt']);

        $postUrl = $cfg['sandbox']
            ? self::SANDBOX_FORM_URL
            : self::PRODUCTION_FORM_URL;

        return [
            'purchase_id' => $purchase->id,
            'status' => $purchase->status,
            'gateway_reference' => $reference,
            'payment_data' => [
                // The client auto-submits this form to JazzCash (WebView or browser).
                'post_url' => $postUrl,
                'payload' => $payload,
            ],
        ];
    }

    public function verifyWebhook(array $payload, array $headers = []): array
    {
        $cfg = $this->config();
        $this->ensureConfigured($cfg);

        $received = $payload['pp_SecureHash'] ?? null;
        $expected = self::computeSecureHash($payload, $cfg['integrity_salt']);

        if (! is_string($received) || ! hash_equals($expected, strtoupper($received))) {
            throw new RuntimeException('Invalid JazzCash secure hash — payload rejected.');
        }

        return [
            'status' => ($payload['pp_ResponseCode'] ?? null) === '000' ? 'completed' : 'failed',
            'gateway_reference' => $payload['pp_TxnRefNo'] ?? null,
            'raw' => $payload,
        ];
    }

    /**
     * Official algorithm: sort every pp* field (ASCII ascending), join values
     * with '&', prepend "<salt>&", HMAC-SHA256 keyed with the salt.
     * Verified against the sandbox guide's worked example
     * (salt 3vv9wu3a18 + MC25041/sz1v4agvyf/T20220203110109).
     */
    public static function computeSecureHash(array $payload, string $salt): string
    {
        $fields = [];

        foreach ($payload as $key => $value) {
            if ($key === 'pp_SecureHash' || ! str_starts_with(strtolower((string) $key), 'pp')) {
                continue;
            }
            $fields[(string) $key] = (string) $value;
        }

        ksort($fields, SORT_STRING);

        return strtoupper(hash_hmac('sha256', $salt.'&'.implode('&', $fields), $salt));
    }

    protected function config(): array
    {
        return config('payments.jazzcash');
    }

    protected function ensureConfigured(array $cfg): void
    {
        if (empty($cfg['merchant_id']) || empty($cfg['integrity_salt'])) {
            throw new RuntimeException('JazzCash merchant credentials are not configured yet (see config/payments.php).');
        }
    }
}
