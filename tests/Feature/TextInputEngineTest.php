<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Test as SmartTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextInputEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private SmartTest $test;

    private Question $textQuestion;

    private Question $multiWordQuestion;

    private Question $mcqQuestion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create(['role' => 'student']);
        $subject = Subject::create(['name' => 'IELTS Academic', 'slug' => 'ielts-academic']);
        $module = Module::create(['subject_id' => $subject->id, 'name' => 'Reading Module']);
        $this->test = SmartTest::create([
            'module_id' => $module->id,
            'title' => 'Reading Practice Test 1',
            'kind' => 'practice',
            'timed' => true,
            'duration_minutes' => 60,
            'is_free' => true,
            'status' => 'published',
        ]);
        $this->textQuestion = Question::create([
            'test_id' => $this->test->id,
            'qtype' => 'text_input',
            'question_text' => 'Prof Zeman is ___ that aphantasia is not an illness.',
            'options' => [],
            'correct_options' => ['Adamant'],
            'marks' => 1,
            'sort_order' => 1,
        ]);
        $this->multiWordQuestion = Question::create([
            'test_id' => $this->test->id,
            'qtype' => 'text_input',
            'question_text' => 'Hyperphantasia is ___ to aphantasia.',
            'options' => [],
            'correct_options' => ['Polar-opposite'],
            'marks' => 1,
            'sort_order' => 2,
        ]);
        $this->mcqQuestion = Question::create([
            'test_id' => $this->test->id,
            'qtype' => 'mcq',
            'question_text' => 'Pick the vowel.',
            'options' => ['B', 'A'],
            'correct_options' => [1],
            'marks' => 1,
            'sort_order' => 3,
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer '.$this->student->createToken('test')->plainTextToken];
    }

    private function startAttempt(): int
    {
        return $this->withHeaders($this->headers())
            ->postJson("/api/tests/{$this->test->id}/start")
            ->assertCreated()
            ->json('attempt_id');
    }

    public function test_start_payload_includes_text_questions_without_leaking_answers(): void
    {
        $response = $this->withHeaders($this->headers())
            ->postJson("/api/tests/{$this->test->id}/start")
            ->assertCreated();

        $questions = collect($response->json('questions'));
        $text = $questions->firstWhere('qtype', 'text_input');

        $this->assertNotNull($text);
        $this->assertSame([], $text['options']);
        $this->assertArrayNotHasKey('correct_options', $text);
    }

    public function test_typed_answers_are_saved_and_scored_with_normalization(): void
    {
        $attemptId = $this->startAttempt();

        // Auto-save stores the typed text.
        $this->withHeaders($this->headers())
            ->postJson("/api/test-attempts/{$attemptId}/answers", [
                'answers' => [
                    ['question_id' => $this->textQuestion->id, 'text_answer' => 'Adamant'],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('attempt_answers', [
            'attempt_id' => $attemptId,
            'question_id' => $this->textQuestion->id,
            'text_answer' => 'Adamant',
        ]);

        // Submit: case difference forgiven; hyphen typed as a space forgiven;
        // the MCQ in the same attempt still scores by index.
        // (score serializes as a decimal string, e.g. "3.00" — compare loosely.)
        $submit = $this->withHeaders($this->headers())
            ->postJson("/api/test-attempts/{$attemptId}/submit", [
                'answers' => [
                    ['question_id' => $this->textQuestion->id, 'text_answer' => 'adamant'],
                    ['question_id' => $this->multiWordQuestion->id, 'text_answer' => '  Polar   Opposite '],
                    ['question_id' => $this->mcqQuestion->id, 'selected' => [1]],
                ],
            ])
            ->assertOk();

        $this->assertEquals(3, $submit->json('score'));
        $this->assertEquals(3, $submit->json('total_marks'));
    }

    public function test_wrong_spelling_and_empty_answers_score_zero(): void
    {
        $attemptId = $this->startAttempt();

        $submit = $this->withHeaders($this->headers())
            ->postJson("/api/test-attempts/{$attemptId}/submit", [
                'answers' => [
                    ['question_id' => $this->textQuestion->id, 'text_answer' => 'Adamantly'],
                    ['question_id' => $this->multiWordQuestion->id, 'text_answer' => ''],
                    ['question_id' => $this->mcqQuestion->id, 'selected' => [0]],
                ],
            ])
            ->assertOk();

        $this->assertEquals(0, $submit->json('score'));
    }

    public function test_results_payload_exposes_typed_answer_and_correct_strings(): void
    {
        $attemptId = $this->startAttempt();

        $this->withHeaders($this->headers())
            ->postJson("/api/test-attempts/{$attemptId}/submit", [
                'answers' => [
                    ['question_id' => $this->textQuestion->id, 'text_answer' => 'Adamant'],
                    ['question_id' => $this->multiWordQuestion->id, 'text_answer' => 'Network'],
                    ['question_id' => $this->mcqQuestion->id, 'selected' => [1]],
                ],
            ])
            ->assertOk();

        $response = $this->withHeaders($this->headers())
            ->getJson("/api/test-attempts/{$attemptId}/results")
            ->assertOk();

        $byQuestion = collect($response->json('questions'))->keyBy('question_id');

        $typed = $byQuestion[$this->textQuestion->id];
        $this->assertSame('Adamant', $typed['text_answer']);
        $this->assertSame(['Adamant'], $typed['selected_labels']);
        $this->assertSame(['Adamant'], $typed['correct_labels']);
        $this->assertTrue($typed['is_correct']);

        $wrong = $byQuestion[$this->multiWordQuestion->id];
        $this->assertSame('Network', $wrong['text_answer']);
        $this->assertSame(['Polar-opposite'], $wrong['correct_labels']);
        $this->assertFalse($wrong['is_correct']);
    }
}
