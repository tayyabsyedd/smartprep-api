<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Module;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected Subject $subject;
    protected Module $module;
    protected Document $publicDoc;
    protected Document $enrolledDoc;

    protected function notesPath(string $file): string
    {
        return storage_path('app/notes/'.$file);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Fixtures go straight to storage/app/notes (the private notes store).
        // Note: Storage::disk('local') points at storage/app/private in
        // Laravel 11+, so it must NOT be used for notes fixtures.
        @mkdir(storage_path('app/notes'), 0755, true);
        file_put_contents($this->notesPath('test-public.pdf'), 'fake-pdf-bytes-public');
        file_put_contents($this->notesPath('test-enrolled.pdf'), 'fake-pdf-bytes-enrolled');

        $this->subject = Subject::create(['name' => 'Physics', 'slug' => 'physics']);
        $this->module = Module::create(['subject_id' => $this->subject->id, 'name' => 'Mechanics']);

        $this->publicDoc = Document::create([
            'subject_id' => $this->subject->id,
            'module_id' => $this->module->id,
            'chapter' => 'Kinematics',
            'title' => 'Public Sheet',
            'file_path' => 'notes/test-public.pdf',
            'visibility' => 'public',
        ]);

        $this->enrolledDoc = Document::create([
            'subject_id' => $this->subject->id,
            'module_id' => $this->module->id,
            'chapter' => 'Dynamics',
            'title' => 'Enrolled Notes',
            'file_path' => 'notes/test-enrolled.pdf',
            'visibility' => 'enrolled',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->notesPath('test-public.pdf'));
        @unlink($this->notesPath('test-enrolled.pdf'));

        parent::tearDown();
    }

    protected function headers(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('mobile')->plainTextToken];
    }

    protected function docIds(TestResponse $response): array
    {
        $ids = [];

        foreach ($response->json('subjects') as $subject) {
            foreach ($subject['modules'] as $module) {
                foreach ($module['chapters'] as $chapter) {
                    foreach ($chapter['documents'] as $doc) {
                        $ids[] = $doc['id'];
                    }
                }
            }
        }

        return $ids;
    }

    public function test_guest_sees_only_public_documents(): void
    {
        $ids = $this->docIds($this->getJson('/api/documents')->assertOk());

        $this->assertContains($this->publicDoc->id, $ids);
        $this->assertNotContains($this->enrolledDoc->id, $ids);
    }

    public function test_non_enrolled_student_sees_only_public_documents(): void
    {
        $student = User::factory()->create(['is_enrolled' => false]);

        $ids = $this->docIds($this->getJson('/api/documents', $this->headers($student))->assertOk());

        $this->assertContains($this->publicDoc->id, $ids);
        $this->assertNotContains($this->enrolledDoc->id, $ids);
    }

    public function test_enrolled_student_sees_all_documents(): void
    {
        $student = User::factory()->create(['is_enrolled' => true]);

        $ids = $this->docIds($this->getJson('/api/documents', $this->headers($student))->assertOk());

        $this->assertContains($this->publicDoc->id, $ids);
        $this->assertContains($this->enrolledDoc->id, $ids);
    }

    public function test_index_structure_groups_by_subject_module_chapter(): void
    {
        $this->getJson('/api/documents')
            ->assertOk()
            ->assertJsonPath('subjects.0.name', 'Physics')
            ->assertJsonPath('subjects.0.modules.0.name', 'Mechanics')
            ->assertJsonPath('subjects.0.modules.0.chapters.0.chapter', 'Kinematics')
            ->assertJsonPath('subjects.0.modules.0.chapters.0.documents.0.title', 'Public Sheet');
    }

    public function test_guest_can_stream_public_document(): void
    {
        $response = $this->get("/api/documents/{$this->publicDoc->id}/stream");

        $response->assertOk();
        $this->assertSame('fake-pdf-bytes-public', $response->streamedContent());
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_guest_cannot_stream_enrolled_document(): void
    {
        $this->get("/api/documents/{$this->enrolledDoc->id}/stream")->assertForbidden();
    }

    public function test_non_enrolled_student_cannot_stream_enrolled_document(): void
    {
        $student = User::factory()->create(['is_enrolled' => false]);

        $this->get("/api/documents/{$this->enrolledDoc->id}/stream", $this->headers($student))
            ->assertForbidden();
    }

    public function test_enrolled_student_can_stream_enrolled_document(): void
    {
        $student = User::factory()->create(['is_enrolled' => true]);

        $response = $this->get("/api/documents/{$this->enrolledDoc->id}/stream", $this->headers($student));

        $response->assertOk();
        $this->assertSame('fake-pdf-bytes-enrolled', $response->streamedContent());
    }

    public function test_stream_returns_404_when_file_missing(): void
    {
        @unlink($this->notesPath('test-public.pdf'));

        $this->get("/api/documents/{$this->publicDoc->id}/stream")->assertNotFound();
    }

    public function test_stream_blocks_path_traversal(): void
    {
        $evil = Document::create([
            'subject_id' => $this->subject->id,
            'module_id' => $this->module->id,
            'chapter' => 'Evil',
            'title' => 'Evil',
            'file_path' => 'notes/../../.env',
            'visibility' => 'public',
        ]);

        $this->get("/api/documents/{$evil->id}/stream")->assertNotFound();
    }
}
