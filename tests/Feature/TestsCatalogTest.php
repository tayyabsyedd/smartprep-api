<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestsCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function headers(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('mobile')->plainTextToken];
    }

    protected function seedCatalog(): Module
    {
        $subject = Subject::create(['name' => 'Physics', 'slug' => 'physics']);
        $module = Module::create(['subject_id' => $subject->id, 'name' => 'Mechanics']);

        $test = Test::create([
            'module_id' => $module->id,
            'title' => 'Free Practice',
            'kind' => 'practice',
            'timed' => false,
            'is_free' => true,
            'price' => null,
            'status' => 'published',
        ]);
        Question::create([
            'test_id' => $test->id, 'qtype' => 'mcq',
            'question_text' => 'Q1', 'options' => ['A', 'B'],
            'correct_options' => [0], 'marks' => 1, 'sort_order' => 1,
        ]);

        // Unpublished tests must not appear in the catalog.
        Test::create([
            'module_id' => $module->id,
            'title' => 'Draft Mock',
            'kind' => 'mock',
            'timed' => true,
            'duration_minutes' => 60,
            'is_free' => false,
            'price' => 499,
            'status' => 'draft',
        ]);

        return $module;
    }

    public function test_catalog_index_returns_structured_tree(): void
    {
        $this->seedCatalog();
        $student = User::factory()->create(['is_enrolled' => false]);

        $response = $this->getJson('/api/tests', $this->headers($student))->assertOk();

        $response->assertJsonPath('subjects.0.name', 'Physics');
        $response->assertJsonPath('subjects.0.modules.0.name', 'Mechanics');
        $response->assertJsonPath('subjects.0.modules.0.free_tests_remaining', 2);

        $tests = $response->json('subjects.0.modules.0.tests');
        $this->assertCount(1, $tests);
        $this->assertSame('Free Practice', $tests[0]['title']);
        $this->assertSame(1, $tests[0]['question_count']);
        $this->assertTrue($tests[0]['is_free']);
    }

    public function test_catalog_quota_reflects_enrollment(): void
    {
        $this->seedCatalog();
        $enrolled = User::factory()->create(['is_enrolled' => true]);

        $this->getJson('/api/tests', $this->headers($enrolled))
            ->assertOk()
            ->assertJsonPath('subjects.0.modules.0.free_tests_remaining', 5);
    }

    public function test_catalog_requires_auth(): void
    {
        $this->getJson('/api/tests')->assertUnauthorized();
    }
}
