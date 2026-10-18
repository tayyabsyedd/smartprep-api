<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutoring_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained('tutor_slots')->cascadeOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->string('meeting_url')->nullable();
            $table->enum('meeting_provider', ['zoom', 'meet'])->nullable();
            $table->enum('status', ['booked', 'completed', 'cancelled', 'no_show'])->default('booked');
            $table->timestamp('reminder_24h_sent_at')->nullable();
            $table->timestamp('reminder_1h_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutoring_sessions');
    }
};
