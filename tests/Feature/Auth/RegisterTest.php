<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
    }

    private function bypassOtp(string $email): void
    {
        Cache::put("email_otp_verified:{$email}", true, 600);
    }

    public function test_user_can_register(): void
    {
        $email = 'new@example.com';
        $this->bypassOtp($email);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'New User',
            'email'                 => $email,
            'password'              => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'phone'                 => '+218911234567',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user', 'access_token']]);

        $this->assertDatabaseHas('users', ['email' => $email]);
    }

    public function test_registration_fails_without_otp(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'No OTP',
            'email'                 => 'no-otp@example.com',
            'password'              => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]);

        $response->assertStatus(403);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Duplicate',
            'email'                 => 'existing@example.com',
            'password'              => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]);

        $response->assertStatus(422);
    }
}
