<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['in_progress', 'submitted', 'expired', 'abandoned'])->default('in_progress');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->decimal('score', 8, 2)->nullable();
            $table->unsignedInteger('total_marks')->default(0);
            $table->unsignedInteger('app_switches')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'test_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};
