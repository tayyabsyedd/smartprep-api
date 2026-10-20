<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * IELTS-style tests attach a reading passage to a group of questions.
     * The passage text lives on the FIRST question of each passage section
     * (nullable on all others) so the test runner can render it alongside
     * that section's questions.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->text('passage_text')->nullable()->after('question_text');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('passage_text');
        });
    }
};
