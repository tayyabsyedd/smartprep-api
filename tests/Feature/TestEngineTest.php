<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Module $module;
    protected Test $freeTest;
    protected Test $paidTest;
    protected array $q; // question ids keyed by label

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create(['is_enrolled' => false]);

        $subject = Subject::create(['name' => 'Physics', 'slug' => 'physics']);
        $this->module = Module::create(['subject_id' => $subject->id, 'name' => 'Mechanics']);

        $this->freeTest = $this->makeTest('Free Practice', true, null);
        $this->paidTest = $this->makeTest('Paid Mock', false, 499.00);
    }

    protected function makeTest(string $title, bool $isFree, ?float $price): Test
    {
        $test = Test::create([
            'module_id' => $this->module->id,
            'title' => $title,
            'kind' => 'practice',
            'timed' => false,
            'is_free' => $isFree,
            'price' => $price,
            'status' => 'published',
        ]);

        $q1 = Question::create([
            'test_id' => $test->id, 'qtype' => 'mcq',
            'question_text' => 'Q1', 'options' => ['A', 'B', 'C'],
            'correct_options' => [1], 'explanation' => 'Because B.',
            'marks' => 1, 'sort_order' => 1,
        ]);
        $q2 = Question::create([
            'test_id' => $test->id, 'qtype' => 'multi_select',
            'question_text' => 'Q2', 'options' => ['A', 'B', 'C', 'D'],
            'correct_options' => [0, 2], 'explanation' => 'A and C.',
            'marks' => 2, 'sort_order' => 2,
        ]);

        $this->q[$test->id] = [$q1->id, $q2->id];

        return $test;
    }

    protected function headers(?User $user = null): array
    {
        $user ??= $this->student;

        return ['Authorization' => 'Bearer '.$user->createToken('mobile')->plainTextToken];
    }

    protected function submitQuotaAttempt(Test $test): void
    {
        TestAttempt::create([
            'user_id' => $this->student->id,
            'test_id' => $test->id,
            'status' => 'submitted',
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
            'total_marks' => 3,
            'used_free_quota' => true,
        ]);
    }

    public function test_show_returns_metadata_for_free_test(): void
    {
        $this->getJson("/api/tests/{$this->freeTest->id}", $this->headers())
            ->assertOk()
            ->assertJsonPath('title', 'Free Practice')
            ->assertJsonPath('question_count', 2)
            ->assertJsonPath('total_marks', 3)
            ->assertJsonPath('access.via', 'free')
            ->assertJsonPath('module.free_tests_remaining', 2);
    }

    public function test_show_blocks_paywalled_test_with_quota_exceeded_payload(): void
    {
        $this->submitQuotaAttempt($this->paidTest);
        $this->submitQuotaAttempt($this->paidTest);

        $this->getJson("/api/tests/{$this->paidTest->id}", $this->headers())
            ->assertForbidden()
            ->assertJson([
                'error' => 'quota_exceeded',
                'message' => 'Purchase required to attempt this test.',
                'test_id' => $this->paidTest->id,
            ]);
    }

    public function test_show_allows_paid_test_within_quota(): void
    {
        $this->getJson("/api/tests/{$this->paidTest->id}", $this->headers())
            ->assertOk()
            ->assertJsonPath('access.via', 'quota');
    }

    public function test_start_creates_attempt_with_sanitized_questions(): void
    {
        $response = $this->postJson("/api/tests/{$this->paidTest->id}/start", [], $this->headers());

        $response->assertCreated()
            ->assertJsonStructure(['attempt_id', 'questions']);

        $questions = $response->json('questions');
        $this->assertCount(2, $questions);
        $this->assertSame(['question_id', 'qtype', 'question_text', 'options', 'marks'], array_keys($questions[0]));
        $this->assertArrayNotHasKey('correct_options', $questions[0]);
        $this->assertArrayNotHasKey('explanation', $questions[0]);

        $this->assertDatabaseHas('test_attempts', [
            'id' => $response->json('attempt_id'),
            'user_id' => $this->student->id,
            'test_id' => $this->paidTest->id,
            'status' => 'in_progress',
            'used_free_quota' => true,
        ]);
    }

    public function test_start_is_denied_when_paywalled(): void
    {
        $this->submitQuotaAttempt($this->paidTest);
        $this->submitQuotaAttempt($this->paidTest);

        $this->postJson("/api/tests/{$this->paidTest->id}/start", [], $this->headers())
            ->assertForbidden()
            ->assertJsonPath('error', 'quota_exceeded');
    }

    public function test_start_resumes_existing_in_progress_attempt(): void
    {
        $first = $this->postJson("/api/tests/{$this->freeTest->id}/start", [], $this->headers())
            ->assertCreated();

        $second = $this->postJson("/api/tests/{$this->freeTest->id}/start", [], $this->headers())
            ->assertOk()
            ->assertJsonPath('resumed', true);

        $this->assertSame($first->json('attempt_id'), $second->json('attempt_id'));
        $this->assertSame(1, TestAttempt::where('user_id', $this->student->id)->count());
    }

    public function test_entitlement_bypasses_exhausted_quota(): void
    {
        $this->submitQuotaAttempt($this->paidTest);
        $this->submitQuotaAttempt($this->paidTest);

        // Guest quota exhausted — but a purchase grants access.
        $purchase = \App\Models\Purchase::create([
            'user_id' => $this->student->id,
            'purchasable_type' => Test::class,
            'purchasable_id' => $this->paidTest->id,
            'amount' => 499.00,
            'payment_method' => 'bank_transfer',
        ]);
        app(PaymentManager::class)->complete($purchase);

        $this->getJson("/api/tests/{$this->paidTest->id}", $this->headers())
            ->assertOk()
            ->assertJsonPath('access.via', 'entitlement');
    }

    public function test_full_flow_start_submit_results(): void
    {
        [$q1, $q2] = $this->q[$this->paidTest->id];

        $attemptId = $this->postJson("/api/tests/{$this->paidTest->id}/start", [], $this->headers())
            ->assertCreated()
            ->json('attempt_id');

        // Q1 correct (1 mark), Q2 partially wrong (0 of 2 marks).
        $submit = $this->postJson("/api/test-attempts/{$attemptId}/submit", [
            'answers' => [
                ['question_id' => $q1, 'selected' => [1], 'time_spent_seconds' => 12],
                ['question_id' => $q2, 'selected' => [0], 'time_spent_seconds' => 30],
            ],
            'app_switches' => 2,
        ], $this->headers());

        $submit->assertOk()
            ->assertJsonPath('score', '1.00')
            ->assertJsonPath('total_marks', 3);

        $this->assertDatabaseHas('attempt_answers', [
            'attempt_id' => $attemptId, 'question_id' => $q1, 'is_correct' => true,
        ]);
        $this->assertDatabaseHas('attempt_answers', [
            'attempt_id' => $attemptId, 'question_id' => $q2, 'is_correct' => false,
        ]);
        $this->assertDatabaseHas('test_attempts', [
            'id' => $attemptId, 'status' => 'submitted', 'app_switches' => 2,
        ]);

        // Quota consumed by the paid quota attempt.
        $this->assertSame(1, $this->student->freeTestsRemaining($this->module));

        $results = $this->getJson("/api/test-attempts/{$attemptId}/results", $this->headers())
            ->assertOk();

        $results->assertJsonPath('score', '1.00')
            ->assertJsonPath('percentage', 33.3)
            ->assertJsonPath('passed', false)
            ->assertJsonPath('app_switches', 2);

        $questions = $results->json('questions');
        $this->assertCount(2, $questions);
        // Answers revealed only AFTER submission.
        $this->assertSame([1], $questions[0]['correct_options']);
        $this->assertSame('Because B.', $questions[0]['explanation']);
        $this->assertTrue($questions[0]['is_correct']);
        $this->assertFalse($questions[1]['is_correct']);
    }

    public function test_multi_select_requires_exact_set(): void
    {
        [$q1, $q2] = $this->q[$this->freeTest->id];

        $attemptId = $this->postJson("/api/tests/{$this->freeTest->id}/start", [], $this->headers())
            ->json('attempt_id');

        // Correct set, different order → still correct.
        $this->postJson("/api/test-attempts/{$attemptId}/submit", [
            'answers' => [
                ['question_id' => $q1, 'selected' => [1], 'time_spent_seconds' => 5],
                ['question_id' => $q2, 'selected' => [2, 0], 'time_spent_seconds' => 5],
            ],
        ], $this->headers())->assertOk()->assertJsonPath('score', '3.00');
    }

    public function test_submit_rejects_other_users_attempt(): void
    {
        $other = User::factory()->create();
        $attempt = TestAttempt::create([
            'user_id' => $other->id,
            'test_id' => $this->freeTest->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'total_marks' => 3,
        ]);

        [$q1] = $this->q[$this->freeTest->id];

        $this->postJson("/api/test-attempts/{$attempt->id}/submit", [
            'answers' => [['question_id' => $q1, 'selected' => [1]]],
        ], $this->headers())->assertForbidden();
    }

    public function test_submit_rejects_already_submitted_attempt(): void
    {
        $attempt = TestAttempt::create([
            'user_id' => $this->student->id,
            'test_id' => $this->freeTest->id,
            'status' => 'submitted',
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
            'total_marks' => 3,
        ]);

        [$q1] = $this->q[$this->freeTest->id];

        $this->postJson("/api/test-attempts/{$attempt->id}/submit", [
            'answers' => [['question_id' => $q1, 'selected' => [1]]],
        ], $this->headers())->assertUnprocessable();
    }

    public function test_results_forbidden_before_submit(): void
    {
        $attemptId = $this->postJson("/api/tests/{$this->freeTest->id}/start", [], $this->headers())
            ->json('attempt_id');

        $this->getJson("/api/test-attempts/{$attemptId}/results", $this->headers())
            ->assertForbidden();
    }

    public function test_test_history_returns_totals(): void
    {
        [$q1, $q2] = $this->q[$this->freeTest->id];

        $attemptId = $this->postJson("/api/tests/{$this->freeTest->id}/start", [], $this->headers())
            ->json('attempt_id');

        // Simulate a 5-minute attempt so practice time is measurable.
        TestAttempt::where('id', $attemptId)->update(['started_at' => now()->subMinutes(5)]);

        $this->postJson("/api/test-attempts/{$attemptId}/submit", [
            'answers' => [
                ['question_id' => $q1, 'selected' => [1], 'time_spent_seconds' => 10],
                ['question_id' => $q2, 'selected' => [0, 2], 'time_spent_seconds' => 20],
            ],
        ], $this->headers())->assertOk();

        $history = $this->getJson('/api/profile/test-history', $this->headers())
            ->assertOk();

        $history->assertJsonPath('total_tests_taken', 1)
            ->assertJsonPath('attempts.0.test_title', 'Free Practice')
            ->assertJsonPath('attempts.0.percentage', 100);

        $this->assertGreaterThanOrEqual(300, $history->json('cumulative_practice_seconds'));
    }

    public function test_free_tests_do_not_consume_quota(): void
    {
        $this->assertSame(2, $this->student->freeTestsRemaining($this->module));

        [$q1, $q2] = $this->q[$this->freeTest->id];
        $attemptId = $this->postJson("/api/tests/{$this->freeTest->id}/start", [], $this->headers())
            ->json('attempt_id');
        $this->postJson("/api/test-attempts/{$attemptId}/submit", [
            'answers' => [
                ['question_id' => $q1, 'selected' => [1], 'time_spent_seconds' => 5],
                ['question_id' => $q2, 'selected' => [0, 2], 'time_spent_seconds' => 5],
            ],
        ], $this->headers())->assertOk();

        // Free practice test submitted — quota untouched.
        $this->assertSame(2, $this->student->fresh()->freeTestsRemaining($this->module));
    }
}
