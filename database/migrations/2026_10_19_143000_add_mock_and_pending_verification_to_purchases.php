<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the 'mock' payment method and the 'pending_verification' status
     * (manual bank transfers awaiting admin approval).
     *
     * MySQL: ALTER COLUMN. SQLite: Laravel 13 enforces enums via CHECK
     * constraints, which SQLite cannot alter — so the table is rebuilt.
     */
    public function up(): void
    {
        $this->alterEnums(
            ['jazzcash', 'easypaisa', 'bank_transfer', 'play_billing', 'mock'],
            ['pending', 'pending_verification', 'completed', 'failed', 'refunded']
        );
    }

    public function down(): void
    {
        $this->alterEnums(
            ['jazzcash', 'easypaisa', 'bank_transfer', 'play_billing'],
            ['pending', 'completed', 'failed', 'refunded']
        );
    }

    protected function alterEnums(array $methods, array $statuses): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $methodsList = "'".implode("','", $methods)."'";
            $statusesList = "'".implode("','", $statuses)."'";

            DB::statement("ALTER TABLE purchases MODIFY COLUMN payment_method ENUM({$methodsList}) NOT NULL");
            DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM({$statusesList}) NOT NULL DEFAULT 'pending'");

            return;
        }

        if ($driver === 'sqlite') {
            $this->rebuildSqliteTable($methods, $statuses);
        }
    }

    protected function rebuildSqliteTable(array $methods, array $statuses): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('purchases_new', function (Blueprint $table) use ($methods, $statuses) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purchasable_type');
            $table->unsignedBigInteger('purchasable_id');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('PKR');
            $table->enum('payment_method', $methods);
            $table->enum('status', $statuses)->default('pending');
            $table->string('gateway_reference')->nullable()->index();
            $table->json('gateway_response')->nullable();
            $table->string('proof_image_path')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();

            $table->index(['purchasable_type', 'purchasable_id']);
            $table->index(['user_id', 'status']);
        });

        DB::statement('INSERT INTO purchases_new
            (id, user_id, purchasable_type, purchasable_id, amount, currency, payment_method, status,
             gateway_reference, gateway_response, proof_image_path, purchased_at, created_at, updated_at)
            SELECT id, user_id, purchasable_type, purchasable_id, amount, currency, payment_method, status,
             gateway_reference, gateway_response, proof_image_path, purchased_at, created_at, updated_at
            FROM purchases');

        Schema::drop('purchases');
        Schema::rename('purchases_new', 'purchases');

        Schema::enableForeignKeyConstraints();
    }
};
