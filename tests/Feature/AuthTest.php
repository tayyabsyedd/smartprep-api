<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test Student',
            'email' => 'student@test.local',
            'phone' => '03009998887',
            'password' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token', 'user' => ['id', 'is_enrolled', 'modules', 'entitlements']]);

        $this->assertDatabaseHas('users', ['email' => 'student@test.local', 'role' => 'student']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@test.local']);

        $this->postJson('/api/auth/register', [
            'name' => 'Someone',
            'email' => 'dup@test.local',
            'password' => 'secret123',
        ])->assertUnprocessable();
    }

    public function test_login_accepts_email_or_phone(): void
    {
        User::factory()->create([
            'email' => 'login@test.local',
            'phone' => '03001112223',
            'password' => bcrypt('secret123'),
        ]);

        $this->postJson('/api/auth/login', ['login' => 'login@test.local', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure(['token']);

        $this->postJson('/api/auth/login', ['login' => '03001112223', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create(['email' => 'bad@test.local', 'password' => bcrypt('secret123')]);

        $this->postJson('/api/auth/login', ['login' => 'bad@test.local', 'password' => 'wrongpass'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_me_returns_profile_and_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/api/auth/me', $headers)
            ->assertOk()
            ->assertJsonPath('email', $user->email)
            ->assertJsonPath('is_enrolled', false)
            ->assertJsonStructure(['modules', 'entitlements']);

        $this->postJson('/api/auth/logout', [], $headers)->assertOk();

        // The auth guard memoizes the user inside one test process; forget it
        // so the next call re-authenticates like a real separate HTTP request.
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/auth/me', $headers)->assertUnauthorized();
    }

    public function test_guests_cannot_reach_protected_routes(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $this->postJson('/api/auth/logout')->assertUnauthorized();
        $this->postJson('/api/enrollment/verify', ['roll_id' => 'ROLL-101'])->assertUnauthorized();
    }
}
