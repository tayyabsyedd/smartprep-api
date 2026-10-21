<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\Purchase;
use App\Models\Subject;
use App\Models\Test as SmartTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }

    public function test_student_cannot_access_admin_panel(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/enrollment-requests')->assertOk();
        $this->actingAs($admin)->get('/admin/purchases')->assertOk();
    }

    public function test_admin_can_view_a_payment_slip_and_students_cannot(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment_slips/slip-1.pdf', '%PDF-1.4 fake');

        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::create(['name' => 'IELTS Academic', 'slug' => 'ielts-academic']);
        $module = Module::create(['subject_id' => $subject->id, 'name' => 'Reading Module']);
        $test = SmartTest::create([
            'module_id' => $module->id,
            'title' => 'Reading Practice Test 1',
            'kind' => 'practice',
            'timed' => true,
            'duration_minutes' => 60,
            'is_free' => false,
            'price' => 499,
            'status' => 'published',
        ]);
        $purchase = Purchase::create([
            'user_id' => $student->id,
            'purchasable_type' => SmartTest::class,
            'purchasable_id' => $test->id,
            'amount' => 499,
            'currency' => 'PKR',
            'payment_method' => 'bank_transfer',
            'status' => 'pending_verification',
            'proof_image_path' => 'payment_slips/slip-1.pdf',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.slips.show', $purchase))
            ->assertOk();

        $this->actingAs($student)
            ->get(route('admin.slips.show', $purchase))
            ->assertForbidden();
    }
}
