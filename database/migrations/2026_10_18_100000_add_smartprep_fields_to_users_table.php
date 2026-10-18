<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->unique()->nullable()->after('email');
            $table->enum('role', ['student', 'instructor', 'admin'])->default('student')->after('password');
            $table->boolean('is_enrolled')->default(false)->after('role');
            $table->string('enrollment_id')->nullable()->after('is_enrolled');
            $table->string('fcm_token')->nullable()->after('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'role', 'is_enrolled', 'enrollment_id', 'fcm_token']);
        });
    }
};
