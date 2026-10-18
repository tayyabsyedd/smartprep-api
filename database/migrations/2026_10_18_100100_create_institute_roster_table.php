<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Institute roll-number roster for BR-02 auto-matching.
     * A submitted enrollment_id matching a row here auto-approves;
     * otherwise the request goes to the admin approval queue.
     */
    public function up(): void
    {
        Schema::create('institute_roster', function (Blueprint $table) {
            $table->id();
            $table->string('enrollment_id')->unique();
            $table->string('student_name')->nullable();
            $table->string('program')->nullable();
            $table->string('batch')->nullable();
            $table->boolean('is_claimed')->default(false);
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_roster');
    }
};
