<?php

namespace Tests\Feature\Payments;

use App\Models\Entitlement;
use App\Models\Module;
use App\Models\Purchase;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\Payments\Drivers\EasypaisaDriver;
use App\Services\Payments\Drivers\JazzCashDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $other;
    protected Module $module;
    protected Test $paidTest;

    protected function setUp(): void
    {
        parent::setUp();

        config(['payments.mock.enabled' => true]);

        $this->student = User::factory()->create(['is_enrolled' => false]);
        $this->other = User::factory()->create(['is_enrolled' => false]);

        $subject = Subject::create(['name' => 'Physics', 'slug' => 'physics']);
        $this->module = Module::create(['subject_id' => $subject->id, 'name' => 'Mechanics']);

        $this->paidTest = Test::create([
            'module_id' => $this->module->id,
            'title' => 'Paid Mock',
            'kind' => 'mock',
            'timed' => false,
            'is_free' => false,
            'price' => 499.00,
            'status' => 'published',
        ]);

        Question::create([
            'test_id' => $this->paidTest->id, 'qtype' => 'mcq',
            'question_text' => 'Q1', 'options' => ['A', 'B'],
            'correct_options' => [0], 'explanation' => 'A.',
            'marks' => 1, 'sort_order' => 1,
        ]);
    }

    protected function headers(?User $user = null): array
    {
        $user ??= $this->student;

        return ['Authorization' => 'Bearer '.$user->createToken('mobile')->plainTextToken];
    }

    // ------------------------------------------------------------------
    // Mock driver
    // ------------------------------------------------------------------

    public function test_mock_payment_completes_purchase_and_grants_entitlement(): void
    {
        $response = $this->postJson('/api/purchases/initiate', [
            'purchasable_type' => 'test',
            'purchasable_id' => $this->paidTest->id,
            'payment_method' => 'mock',
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('status', 'completed');

        $purchase = Purchase::findOrFail($response->json('purchase_id'));

        $this->assertSame('completed', $purchase->status);
        $this->assertSame('mock', $purchase->payment_method);
        $this->assertSame('499.00', (string) $purchase->amount);
        $this->assertStringStartsWith('MOCK-', $purchase->gateway_reference);

        $this->assertTrue(Entitlement::where([
            'user_id' => $this->student->id,
            'entitled_type' => Test::class,
            'entitled_id' => $this->paidTest->id,
        ])->whereNull('revoked_at')->exists());
    }

    public function test_mock_payment_unblocks_test_for_quota_exhausted_student(): void
    {
        // Burn the 2 free attempts.
        foreach ([1, 2] as $i) {
            TestAttempt::create([
                'user_id' => $this->student->id,
                'test_id' => $this->paidTest->id,
                'status' => 'submitted',
                'started_at' => now()->subHour(),
                'submitted_at' => now(),
                'total_marks' => 1,
                'used_free_quota' => true,
            ]);
        }

        $this->postJson("/api/tests/{$this->paidTest->id}/start", [], $this->headers())
            ->assertForbidden()
            ->assertJsonPath('error', 'quota_exceeded');

        $this->postJson('/api/purchases/initiate', [
            'purchasable_type' => 'test',
            'purchasable_id' => $this->paidTest->id,
            'payment_method' => 'mock',
        ], $this->headers())->assertCreated();

        // The entitlement now unlocks the test — the paywall is gone.
        $this->postJson("/api/tests/{$this->paidTest->id}/start", [], $this->headers())
            ->assertStatus(201)
            ->assertJsonPath('resumed', false);
    }

    public function test_mock_driver_refuses_when_disabled(): void
    {
        config(['payments.mock.enabled' => false]);

        $this->postJson('/api/purchases/initiate', [
            'purchasable_type' => 'test',
            'purchasable_id' => $this->paidTest->id,
            'payment_method' => 'mock',
        ], $this->headers())->assertUnprocessable()
            ->assertJsonPath('message', 'Mock payments are disabled in this environment.');
    }

    // ------------------------------------------------------------------
    // JazzCash
    // ------------------------------------------------------------------

    public function test_jazzcash_secure_hash_matches_official_worked_example(): void
    {
        // From the JazzCash sandbox integration guide (salt 3vv9wu3a18).
        $hash = JazzCashDriver::computeSecureHash([
            'pp_MerchantID' => 'MC25041',
            'pp_Password' => 'sz1v4agvyf',
            'pp_TxnRefNo' => 'T20220203110109',
        ], '3vv9wu3a18');

        $this->assertSame(
            '18494EE9B220CA4ADBE3ED5B597CCBF26E8C6F8BA205A9199A2EC8B87A2C0673',
            $hash
        );
    }

    public function test_jazzcash_webhook_with_valid_signature_completes_purchase(): void
    {
        config(['payments.jazzcash.merchant_id' => 'MC123', 'payments.jazzcash.integrity_salt' => 'testsalt12345678']);

        $purchase = Purchase::create([
            'user_id' => $this->student->id,
            'purchasable_type' => Test::class,
            'purchasable_id' => $this->paidTest->id,
            'amount' => 499.00,
            'currency' => 'PKR',
            'payment_method' => 'jazzcash',
            'status' => 'pending',
            'gateway_reference' => 'SP-REF-1',
        ]);

        $payload = [
            'pp_TxnRefNo' => 'SP-REF-1',
            'pp_ResponseCode' => '000',
            'pp_Amount' => '49900',
            'pp_MerchantID' => 'MC123',
        ];
        $payload['pp_SecureHash'] = JazzCashDriver::computeSecureHash($payload, 'testsalt12345678');

        $this->postJson('/api/webhooks/jazzcash', $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Webhook processed.');

        $this->assertSame('completed', $purchase->fresh()->status);
        $this->assertTrue($this->student->canAccess(Test::class, $this->paidTest->id));
    }

    public function test_jazzcash_webhook_with_tampered_signature_is_rejected(): void
    {
        config(['payments.jazzcash.merchant_id' => 'MC123', 'payments.jazzcash.integrity_salt' => 'testsalt12345678']);

        $purchase = Purchase::create([
            'user_id' => $this->student->id,
            'purchasable_type' => Test::class,
            'purchasable_id' => $this->paidTest->id,
            'amount' => 499.00,
            'currency' => 'PKR',
            'payment_method' => 'jazzcash',
            'status' => 'pending',
            'gateway_reference' => 'SP-REF-2',
        ]);

        $payload = [
            'pp_TxnRefNo' => 'SP-REF-2',
            'pp_ResponseCode' => '000',
            'pp_Amount' => '49900',
        ];
        $payload['pp_SecureHash'] = JazzCashDriver::computeSecureHash($payload, 'testsalt12345678');

        // Tamper after signing.
        $payload['pp_Amount'] = '100';

        $this->postJson('/api/webhooks/jazzcash', $payload)
            ->assertBadRequest();

        $this->assertSame('pending', $purchase->fresh()->status);
        $this->assertFalse($this->student->canAccess(Test::class, $this->paidTest->id));
    }

    public function test_jazzcash_initiate_without_credentials_explains_itself(): void
    {
        config(['payments.jazzcash.merchant_id' => null]);

        $this->postJson('/api/purchases/initiate', [
            'purchasable_type' => 'test',
            'purchasable_id' => $this->paidTest->id,
            'payment_method' => 'jazzcash',
            'phone_number' => '03001234567',
        ], $this->headers())
            ->assertUnprocessable()
            ->assertJsonPath('message', 'JazzCash merchant credentials are not configured yet (see config/payments.php).');
    }

    public function test_wallet_methods_require_phone_number(): void
    {
        $this->postJson('/api/purchases/initiate', [
            'purchasable_type' => 'test',
            'purchasable_id' => $this->paidTest->id,
            'payment_method' => 'jazzcash',
        ], $this->headers())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone_number');
    }

    // ------------------------------------------------------------------
    // Easypaisa
    // ------------------------------------------------------------------

    public function test_easypaisa_hash_request_round_trips_through_aes(): void
    {
        $paramMap = [
            'amount' => '499.0',
            'orderRefNum' => 'SP-1-ABC',
            'storeId' => '12345',
        ];
        $key = '1234567890123456';

        $hashRequest = EasypaisaDriver::buildHashRequest($paramMap, $key);

        $decrypted = openssl_decrypt(base64_decode($hashRequest), 'AES-128-ECB', $key, OPENSSL_RAW_DATA);

        $this->assertSame('amount=499.0&orderRefNum=SP-1-ABC&storeId=12345', $decrypted);
    }

    public function test_easypaisa_webhook_completes_on_success_postback(): void
    {
        $purchase = Purchase::create([
            'user_id' => $this->student->id,
            'purchasable_type' => Test::class,
            'purchasable_id' => $this->paidTest->id,
            'amount' => 499.00,
            'currency' => 'PKR',
            'payment_method' => 'easypaisa',
            'status' => 'pending',
            'gateway_reference' => 'SP-EP-1',
        ]);

        $this->postJson('/api/webhooks/easypaisa', [
            'orderRefNum' => 'SP-EP-1',
            'status' => 'success',
        ])->assertOk();

        $this->assertSame('completed', $purchase->fresh()->status);
        $this->assertTrue($this->student->canAccess(Test::class, $this->paidTest->id));
    }

    public function test_easypaisa_webhook_rejects_missing_reference(): void
    {
        $this->postJson('/api/webhooks/easypaisa', ['status' => 'success'])
            ->assertBadRequest();
    }

    public function test_webhook_rejects_cross_driver_reference(): void
    {
        config(['payments.jazzcash.merchant_id' => 'MC123', 'payments.jazzcash.integrity_salt' => 'testsalt12345678']);

        // A bank_transfer purchase reference replayed at the JazzCash endpoint.
        $purchase = Purchase::create([
            'user_id' => $this->student->id,
            'purchasable_type' => Test::class,
            'purchasable_id' => $this->paidTest->id,
            'amount' => 499.00,
            'currency' => 'PKR',
            'payment_method' => 'bank_transfer',
            'status' => 'pending_verification',
            'gateway_reference' => 'SP-X-1',
        ]);

        $payload = ['pp_TxnRefNo' => 'SP-X-1', 'pp_ResponseCode' => '000'];
        $payload['pp_SecureHash'] = JazzCashDriver::computeSecureHash($payload, 'testsalt12345678');

        $this->postJson('/api/webhooks/jazzcash', $payload)->assertBadRequest();

        $this->assertSame('pending_verification', $purchase->fresh()->status);
    }

    // ------------------------------------------------------------------
    // Bank transfer + slip upload
    // ------------------------------------------------------------------

    public function test_bank_transfer_initiate_sets_pending_verification(): void
    {
        config([
            'payments.bank.bank_name' => 'Test Bank',
            'payments.bank.account_title' => 'Start Smart Future',
            'payments.bank.iban' => 'PK00TEST0000000000000000',
        ]);

        $response = $this->postJson('/api/purchases/initiate', [
            'purchasable_type' => 'test',
            'purchasable_id' => $this->paidTest->id,
            'payment_method' => 'bank_transfer',
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('status', 'pending_verification')
            ->assertJsonPath('payment_data.bank', 'Test Bank')
            ->assertJsonPath('payment_data.iban', 'PK00TEST0000000000000000');

        // No entitlement until an admin verifies the slip.
        $this->assertFalse($this->student->canAccess(Test::class, $this->paidTest->id));
    }

    public function test_upload_slip_stores_file_and_links_purchase(): void
    {
        Storage::fake('local');

        $purchase = Purchase::create([
            'user_id' => $this->student->id,
            'purchasable_type' => Test::class,
            'purchasable_id' => $this->paidTest->id,
            'amount' => 499.00,
            'currency' => 'PKR',
            'payment_method' => 'bank_transfer',
            'status' => 'pending_verification',
        ]);

        // NOTE: UploadedFile::fake()->image() needs the GD extension, which the
        // dev PHP build lacks — fake a PDF instead (also an accepted type).
        $file = UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf');

        $this->postJson(
            "/api/purchases/{$purchase->id}/upload-slip",
            ['slip' => $file],
            $this->headers()
        )->assertOk()->assertJsonPath('purchase.status', 'pending_verification');

        $path = $purchase->fresh()->proof_image_path;

        $this->assertNotNull($path);
        $this->assertStringStartsWith('payment_slips/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_user_cannot_upload_slip_to_another_users_purchase(): void
    {
        Storage::fake('local');

        $purchase = Purchase::create([
            'user_id' => $this->student->id,
            'purchasable_type' => Test::class,
            'purchasable_id' => $this->paidTest->id,
            'amount' => 499.00,
            'currency' => 'PKR',
            'payment_method' => 'bank_transfer',
            'status' => 'pending_verification',
        ]);

        $file = UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf');

        $this->postJson(
            "/api/purchases/{$purchase->id}/upload-slip",
            ['slip' => $file],
            $this->headers($this->other)
        )->assertForbidden();

        $this->assertNull($purchase->fresh()->proof_image_path);
    }

    public function test_upload_slip_rejects_oversized_files(): void
    {
        $purchase = Purchase::create([
            'user_id' => $this->student->id,
            'purchasable_type' => Test::class,
            'purchasable_id' => $this->paidTest->id,
            'amount' => 499.00,
            'currency' => 'PKR',
            'payment_method' => 'bank_transfer',
            'status' => 'pending_verification',
        ]);

        $file = UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'); // 6MB > 5MB

        $this->postJson(
            "/api/purchases/{$purchase->id}/upload-slip",
            ['slip' => $file],
            $this->headers()
        )->assertUnprocessable()->assertJsonValidationErrors('slip');
    }

    // ------------------------------------------------------------------
    // Auth & scoping
    // ------------------------------------------------------------------

    public function test_unauthenticated_cannot_initiate(): void
    {
        $this->postJson('/api/purchases/initiate', [
            'purchasable_type' => 'test',
            'purchasable_id' => $this->paidTest->id,
            'payment_method' => 'mock',
        ])->assertUnauthorized();
    }

    public function test_my_purchases_only_returns_own(): void
    {
        Purchase::create([
            'user_id' => $this->student->id, 'purchasable_type' => Test::class,
            'purchasable_id' => $this->paidTest->id, 'amount' => 499.00,
            'currency' => 'PKR', 'payment_method' => 'mock', 'status' => 'completed',
        ]);
        Purchase::create([
            'user_id' => $this->other->id, 'purchasable_type' => Test::class,
            'purchasable_id' => $this->paidTest->id, 'amount' => 499.00,
            'currency' => 'PKR', 'payment_method' => 'mock', 'status' => 'completed',
        ]);

        $response = $this->getJson('/api/purchases/my-purchases', $this->headers());

        $response->assertOk();
        $this->assertCount(1, $response->json('purchases'));
        $this->assertSame('Paid Mock', $response->json('purchases.0.item_title'));
    }
}
