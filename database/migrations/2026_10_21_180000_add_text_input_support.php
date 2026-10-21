<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the 'text_input' question type (typed sentence/summary
     * completions — IELTS Reading Q9–13, 22–25, 36–40) and a text_answer
     * column on attempt_answers to store what the student typed.
     *
     * For text questions, questions.correct_options holds an array of
     * acceptable answer STRINGS instead of option indices; scoring
     * compares normalized text (see TestController::textIsCorrect).
     *
     * MySQL: ALTER COLUMN. SQLite: enums are CHECK constraints, so the
     * questions table is rebuilt (same pattern as the purchases enums).
     */
    public function up(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->string('text_answer', 1000)->nullable()->after('selected');
        });

        $this->alterQtype(['mcq', 'true_false', 'multi_select', 'text_input']);
    }

    public function down(): void
    {
        $this->alterQtype(['mcq', 'true_false', 'multi_select']);

        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->dropColumn('text_answer');
        });
    }

    protected function alterQtype(array $types): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $list = "'".implode("','", $types)."'";
            DB::statement("ALTER TABLE questions MODIFY COLUMN qtype ENUM({$list}) NOT NULL DEFAULT 'mcq'");

            return;
        }

        if ($driver === 'sqlite') {
            $this->rebuildSqliteTable($types);
        }
    }

    protected function rebuildSqliteTable(array $types): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('questions_new', function (Blueprint $table) use ($types) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->enum('qtype', $types)->default('mcq');
            $table->text('question_text');
            $table->text('passage_text')->nullable();
            $table->json('options');
            $table->json('correct_options');
            $table->text('explanation')->nullable();
            $table->unsignedInteger('marks')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::statement('INSERT INTO questions_new
            (id, test_id, qtype, question_text, passage_text, options, correct_options,
             explanation, marks, sort_order, created_at, updated_at)
            SELECT id, test_id, qtype, question_text, passage_text, options, correct_options,
             explanation, marks, sort_order, created_at, updated_at
            FROM questions');

        Schema::drop('questions');
        Schema::rename('questions_new', 'questions');

        Schema::enableForeignKeyConstraints();
    }
};
