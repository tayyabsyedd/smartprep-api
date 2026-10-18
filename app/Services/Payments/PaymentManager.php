<?php

namespace App\Services\Payments;

use App\Models\Entitlement;
use App\Models\Purchase;
use App\Services\Payments\Contracts\PaymentDriver;
use App\Services\Payments\Drivers\BankTransferDriver;
use App\Services\Payments\Drivers\EasypaisaDriver;
use App\Services\Payments\Drivers\JazzCashDriver;
use App\Services\Payments\Drivers\PlayBillingDriver;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentManager
{
    /** @var array<string, class-string<PaymentDriver>> */
    protected array $drivers = [
        'jazzcash' => JazzCashDriver::class,
        'easypaisa' => EasypaisaDriver::class,
        'bank_transfer' => BankTransferDriver::class,
        'play_billing' => PlayBillingDriver::class,
    ];

    public function driver(string $method): PaymentDriver
    {
        if (! isset($this->drivers[$method])) {
            throw new InvalidArgumentException("Unknown payment driver [{$method}].");
        }

        return app($this->drivers[$method]);
    }

    /**
     * Mark a purchase completed and grant access to the purchasable.
     * Called by the webhook controller (gateways) or by admin approval
     * (manual bank transfer). Safe to call twice — idempotent.
     */
    public function complete(Purchase $purchase, ?string $gatewayReference = null, ?array $gatewayResponse = null): void
    {
        DB::transaction(function () use ($purchase, $gatewayReference, $gatewayResponse) {
            $purchase->update([
                'status' => 'completed',
                'gateway_reference' => $gatewayReference ?? $purchase->gateway_reference,
                'gateway_response' => $gatewayResponse ?? $purchase->gateway_response,
                'purchased_at' => $purchase->purchased_at ?? now(),
            ]);

            Entitlement::firstOrCreate(
                [
                    'user_id' => $purchase->user_id,
                    'entitled_type' => $purchase->purchasable_type,
                    'entitled_id' => $purchase->purchasable_id,
                    'revoked_at' => null,
                ],
                [
                    'source' => 'purchase',
                    'granted_at' => now(),
                ]
            );
        });
    }

    /**
     * Mark a purchase failed and revoke any live entitlement immediately.
     */
    public function fail(Purchase $purchase, ?array $gatewayResponse = null): void
    {
        DB::transaction(function () use ($purchase, $gatewayResponse) {
            $purchase->update([
                'status' => 'failed',
                'gateway_response' => $gatewayResponse ?? $purchase->gateway_response,
            ]);

            $this->revokeEntitlements($purchase);
        });
    }

    /**
     * Mark a purchase refunded and revoke access immediately.
     */
    public function refund(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            $purchase->update(['status' => 'refunded']);

            $this->revokeEntitlements($purchase);
        });
    }

    protected function revokeEntitlements(Purchase $purchase): void
    {
        Entitlement::where('user_id', $purchase->user_id)
            ->where('entitled_type', $purchase->purchasable_type)
            ->where('entitled_id', $purchase->purchasable_id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
