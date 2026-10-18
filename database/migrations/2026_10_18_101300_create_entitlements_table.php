<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single source of truth for "can this user open this?".
     * Access checks read this table, never purchases directly.
     * Revocation = set revoked_at (history preserved).
     */
    public function up(): void
    {
        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('entitled_type');
            $table->unsignedBigInteger('entitled_id');
            $table->enum('source', ['free_tier', 'purchase', 'enrollment_grant', 'admin_grant']);
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'entitled_type', 'entitled_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlements');
    }
};
