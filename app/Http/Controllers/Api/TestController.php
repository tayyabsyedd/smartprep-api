<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Test\SubmitAttemptRequest;
use App\Models\AttemptAnswer;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Services\AccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function __construct(protected AccessService $access) {}

    /**
     * GET /api/tests/{test} — metadata + access check.
     */
    public function show(Request $request, Test $test)
    {
        if (! $test->isPublished()) {
            abort(404);
        }

        $test->load('module.subject');
        $via = $this->access->ensureCanAttempt($request->user(), $test);

        return response()->json([
            'id' => $test->id,
            'title' => $test->title,
            'description' => $test->description,
            'kind' => $test->kind,
            'timed' => (bool) $test->timed,
            'duration_minutes' => $test->duration_minutes,
            'is_free' => (bool) $test->is_free,
            'price' => $test->price,
            'question_count' => $test->questions()->count(),
            'total_marks' => (int) $test->questions()->sum('marks'),
            'module' => [
                'id' => $test->module->id,
                'name' => $test->module->name,
                'subject' => $test->module->subject?->name,
                'free_tests_remaining' => $request->user()->freeTestsRemaining($test->module),
            ],
            'access' => ['can_attempt' => true, 'via' => $via],
        ]);
    }

    /**
     * POST /api/tests/{test}/start — begin (or resume) an attempt.
     * Questions are sanitized: no correct_options, no explanations.
     */
    public function start(Request $request, Test $test)
    {
        if (! $test->isPublished()) {
            abort(404);
        }

        $user = $request->user();
        $via = $this->access->ensureCanAttempt($user, $test);

        // Resume an in-progress attempt instead of starting a duplicate.
        $attempt = TestAttempt::where('user_id', $user->id)
            ->where('test_id', $test->id)
            ->where('status', 'in_progress')
            ->first();

        if (! $attempt) {
            $attempt = TestAttempt::create([
                'user_id' => $user->id,
                'test_id' => $test->id,
                'status' => 'in_progress',
                'started_at' => now(),
                'duration_seconds' => $test->timed ? $test->duration_minutes * 60 : null,
                'total_marks' => (int) $test->questions()->sum('marks'),
                'used_free_quota' => $via === 'quota',
            ]);
        }

        $questions = $test->questions()->orderBy('sort_order')->get()->map(fn ($question) => [
            'question_id' => $question->id,
            'qtype' => $question->qtype,
            'question_text' => $question->question_text,
            'options' => $question->options,
            'marks' => $question->marks,
        ]);

        return response()->json([
            'attempt_id' => $attempt->id,
            'started_at' => $attempt->started_at->toISOString(),
            'duration_seconds' => $attempt->duration_seconds,
            'resumed' => ! $attempt->wasRecentlyCreated,
            'questions' => $questions,
        ], $attempt->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * POST /api/test-attempts/{attempt}/answers — auto-save answers as the
     * student taps (supports resume after app kill). Not scored until submit.
     */
    public function saveAnswers(Request $request, TestAttempt $attempt)
    {
        $this->ensureOwnInProgress($request, $attempt);

        $data = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.selected' => ['required', 'array'],
            'answers.*.selected.*' => ['integer', 'min:0'],
            'answers.*.time_spent_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $questionIds = $attempt->test->questions()->pluck('id')->all();

        DB::transaction(function () use ($attempt, $data, $questionIds) {
            foreach ($data['answers'] as $answer) {
                if (! in_array($answer['question_id'], $questionIds, true)) {
                    abort(422, 'One or more questions do not belong to this test.');
                }

                AttemptAnswer::updateOrCreate(
                    ['attempt_id' => $attempt->id, 'question_id' => $answer['question_id']],
                    [
                        'selected' => $answer['selected'],
                        'is_correct' => false,
                        'time_spent_seconds' => $answer['time_spent_seconds'] ?? 0,
                    ]
                );
            }
        });

        return response()->json(['message' => 'Answers saved.']);
    }

    /**
     * POST /api/test-attempts/{attempt}/submit — score the attempt.
     */
    public function submit(SubmitAttemptRequest $request, TestAttempt $attempt)
    {
        $this->ensureOwnInProgress($request, $attempt);

        $questionMap = $attempt->test->questions->keyBy('id');

        foreach ($request->answers as $answer) {
            if (! $questionMap->has($answer['question_id'])) {
                return response()->json(
                    ['message' => 'One or more questions do not belong to this test.'],
                    422
                );
            }
        }

        DB::transaction(function () use ($attempt, $request, $questionMap) {
            foreach ($request->answers as $answer) {
                $question = $questionMap->get($answer['question_id']);
                $selected = $answer['selected'] ?? [];

                AttemptAnswer::updateOrCreate(
                    ['attempt_id' => $attempt->id, 'question_id' => $question->id],
                    [
                        'selected' => $selected,
                        'is_correct' => $this->isCorrect($selected, $question->correct_options),
                        'time_spent_seconds' => $answer['time_spent_seconds'] ?? 0,
                    ]
                );
            }

            $attempt->load('answers.question');
            $score = $attempt->answers
                ->filter(fn ($answer) => $answer->is_correct)
                ->sum(fn ($answer) => $answer->question->marks);

            $attempt->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                // Carbon 3 returns signed diffs by default — absolute=true keeps it positive.
                'time_spent_seconds' => $attempt->started_at->diffInSeconds(now(), true),
                'app_switches' => $request->input('app_switches', 0),
                'score' => $score,
            ]);
        });

        return response()->json([
            'message' => 'Test submitted successfully.',
            'attempt_id' => $attempt->id,
            'score' => $attempt->score,
            'total_marks' => $attempt->total_marks,
        ]);
    }

    /**
     * GET /api/test-attempts/{attempt}/results — full scorecard (BR-07, BR-08).
     */
    public function results(Request $request, TestAttempt $attempt)
    {
        if ($attempt->user_id !== $request->user()->id) {
            abort(403, 'This attempt does not belong to you.');
        }

        if ($attempt->status !== 'submitted') {
            return response()->json(
                ['message' => 'Results are available only for submitted attempts.'],
                403
            );
        }

        $attempt->load('answers.question', 'test.module');

        $passPercentage = 50;
        $percentage = $attempt->total_marks > 0
            ? round(($attempt->score / $attempt->total_marks) * 100, 1)
            : 0;

        $breakdown = $attempt->answers->map(function ($answer) {
            $question = $answer->question;
            $options = $question->options ?? [];
            $selected = $answer->selected ?? [];
            $correct = $question->correct_options ?? [];

            return [
                'question_id' => $question->id,
                'qtype' => $question->qtype,
                'question_text' => $question->question_text,
                'options' => $options,
                'selected_options' => $selected,
                'selected_labels' => collect($selected)->map(fn ($i) => $options[$i] ?? null)->values()->all(),
                'correct_options' => $correct,
                'correct_labels' => collect($correct)->map(fn ($i) => $options[$i] ?? null)->values()->all(),
                'is_correct' => (bool) $answer->is_correct,
                'marks' => $question->marks,
                'marks_obtained' => $answer->is_correct ? $question->marks : 0,
                'time_spent_seconds' => $answer->time_spent_seconds,
                'explanation' => $question->explanation,
            ];
        });

        return response()->json([
            'attempt_id' => $attempt->id,
            'test' => [
                'id' => $attempt->test->id,
                'title' => $attempt->test->title,
                'module' => $attempt->test->module->name,
            ],
            'score' => $attempt->score,
            'total_marks' => $attempt->total_marks,
            'percentage' => $percentage,
            'passed' => $percentage >= $passPercentage,
            'pass_percentage' => $passPercentage,
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'app_switches' => $attempt->app_switches,
            'submitted_at' => $attempt->submitted_at?->toISOString(),
            'questions' => $breakdown->values(),
        ]);
    }

    protected function ensureOwnInProgress(Request $request, TestAttempt $attempt): void
    {
        if ($attempt->user_id !== $request->user()->id) {
            abort(403, 'This attempt does not belong to you.');
        }

        if ($attempt->status !== 'in_progress') {
            abort(422, 'This attempt is no longer in progress.');
        }
    }

    /**
     * Order-independent set comparison. An empty selection is never correct.
     */
    protected function isCorrect(array $selected, ?array $correct): bool
    {
        $selected = collect($selected)->map(fn ($v) => (int) $v)->sort()->values()->all();
        $correct = collect($correct ?? [])->map(fn ($v) => (int) $v)->sort()->values()->all();

        return count($selected) > 0 && $selected === $correct;
    }
}
