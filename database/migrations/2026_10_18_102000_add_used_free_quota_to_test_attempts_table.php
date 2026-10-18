<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks attempts that consumed one unit of the module free quota.
     * Free tests (is_free) and entitled (purchased/granted) attempts
     * never consume quota — only paywalled attempts taken via quota do.
     */
    public function up(): void
    {
        Schema::table('test_attempts', function (Blueprint $table) {
            $table->boolean('used_free_quota')->default(false)->after('app_switches');
        });
    }

    public function down(): void
    {
        Schema::table('test_attempts', function (Blueprint $table) {
            $table->dropColumn('used_free_quota');
        });
    }
};
