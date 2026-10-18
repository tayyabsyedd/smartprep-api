<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['booked', 'cancelled', 'attended'])->default('booked');
            $table->timestamp('calendar_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_bookings');
    }
};
