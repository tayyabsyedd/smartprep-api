<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->enum('status', ['open', 'booked', 'cancelled'])->default('open');
            $table->timestamps();

            $table->index(['tutor_id', 'status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_slots');
    }
};
