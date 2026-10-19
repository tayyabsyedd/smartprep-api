<?php

namespace App\Services\Payments\Drivers;

use App\Models\Purchase;
use App\Services\Payments\Contracts\PaymentDriver;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Easypaisa (Telenor Microfinance Bank) "Easypay" driver — sandbox-ready.
 *
 * Classic hosted-checkout flow. Request signing follows the merchant plugin
 * spec: transaction fields are joined as key=value&..., AES-128-ECB
 * encrypted with the Hash Key (16/24/32 chars) and base64-encoded into
 * `merchantHashedReq`, then POSTed with `storeId` to Index.jsf.
 * Uses the staging URL until EASYPAISA_SANDBOX=false.
 *
 * Throws until the client's store credentials are in .env
 * (EASYPAISA_STORE_ID / EASYPAISA_HASH_KEY).
 *
 * NOTE on verifyWebhook(): the classic postback carries no published
 * signature — Easypaisa echoes our orderRefNum back to postBackURL. We
 * match on our unguessable gateway_reference and treat explicit success
 * markers as completed. Confirm the postback field names against the
 * sandbox on first integration (the request signing above is spec-verified).
 */
class EasypaisaDriver implements PaymentDriver
{
    public const SANDBOX_INDEX_URL = 'https://easypaystg.easypaisa.com.pk/easypay/Index.jsf';
    public const PRODUCTION_INDEX_URL = 'https://easypay.easypaisa.com.pk/easypay/Index.jsf';

    public function key(): string
    {
        return 'easypaisa';
    }

    public function initiate(Purchase $purchase): array
    {
        $cfg = $this->config();
        $this->ensureConfigured($cfg);

        // Unguessable reference: the postback handler trusts it, so it must
        // not be enumerable.
        $reference = 'SP-'.$purchase->id.'-'.Str::upper(Str::random(10));
        $purchase->update(['gateway_reference' => $reference]);

        $context = $purchase->gateway_response ?? [];

        // Canonical field order, matching the merchant plugin.
        $paramMap = [
            'amount' => self::formatAmount($purchase->amount),
            'autoRedirect' => '1',
        ];

        if (! empty($purchase->user->email)) {
            $paramMap['emailAddr'] = $purchase->user->email;
        }

        $paramMap['expiryDate'] = now()->addDay()->format('Ymd His');

        if (! empty($context['phone_number'])) {
            $paramMap['mobileNum'] = $context['phone_number'];
        }

        $paramMap['orderRefNum'] = $reference;
        $paramMap['postBackURL'] = url('/api/webhooks/easypaisa');
        $paramMap['storeId'] = $cfg['store_id'];

        $paramMap['merchantHashedReq'] = self::buildHashRequest($paramMap, $cfg['hash_key']);

        $postUrl = $cfg['sandbox']
            ? self::SANDBOX_INDEX_URL
            : self::PRODUCTION_INDEX_URL;

        return [
            'purchase_id' => $purchase->id,
            'status' => $purchase->status,
            'gateway_reference' => $reference,
            'redirect_url' => $postUrl,
            'payment_data' => [
                // The client auto-submits this form (WebView or browser).
                'post_url' => $postUrl,
                'fields' => $paramMap,
            ],
        ];
    }

    public function verifyWebhook(array $payload, array $headers = []): array
    {
        $reference = $payload['orderRefNum'] ?? $payload['order_ref_num'] ?? null;

        if (! $reference) {
            throw new RuntimeException('Easypaisa callback is missing the order reference.');
        }

        $status = strtolower((string) ($payload['status'] ?? $payload['transactionStatus'] ?? ''));
        $completed = in_array($status, ['success', 'successful', 'completed', 'paid'], true);

        return [
            'status' => $completed ? 'completed' : 'failed',
            'gateway_reference' => $reference,
            'raw' => $payload,
        ];
    }

    /**
     * Merchant-spec request signing: key=value&... string, AES-128-ECB
     * encrypted with the hash key, base64-encoded.
     */
    public static function buildHashRequest(array $paramMap, string $hashKey): string
    {
        $mapString = collect($paramMap)
            ->map(fn ($value, $key) => $key.'='.$value)
            ->implode('&');

        $cipher = openssl_encrypt($mapString, 'AES-128-ECB', $hashKey, OPENSSL_RAW_DATA);

        if ($cipher === false) {
            throw new RuntimeException('Failed to encrypt the Easypaisa request.');
        }

        return base64_encode($cipher);
    }

    /**
     * Easypaisa wants "499.0" style amounts (one decimal) for whole rupees.
     */
    public static function formatAmount($amount): string
    {
        $asString = (string) $amount;

        return str_contains($asString, '.') ? $asString : sprintf('%0.1f', $amount);
    }

    protected function config(): array
    {
        return config('payments.easypaisa');
    }

    protected function ensureConfigured(array $cfg): void
    {
        $keyLength = strlen((string) ($cfg['hash_key'] ?? ''));

        if (empty($cfg['store_id']) || ! in_array($keyLength, [16, 24, 32], true)) {
            throw new RuntimeException('Easypaisa store credentials are not configured yet (see config/payments.php).');
        }
    }
}
