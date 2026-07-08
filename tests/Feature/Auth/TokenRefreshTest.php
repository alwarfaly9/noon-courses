<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TokenRefreshTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole('student');
        $this->token = $this->user->createToken('test-access', ['access'])->plainTextToken;
    }

    public function test_user_can_refresh_token(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);
    }

    public function test_refresh_fails_without_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh');
        $response->assertStatus(401);
    }

    public function test_old_token_still_works_during_grace_period(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/auth/user');

        $response->assertOk();
    }
}
