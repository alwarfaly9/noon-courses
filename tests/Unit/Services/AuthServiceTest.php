<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AuthService::class);
    }

    public function test_creates_token_pair(): void
    {
        $user = User::factory()->create();

        $pair = $this->service->createTokenPair($user, 'test-device');

        $this->assertArrayHasKey('access_token', $pair);
        $this->assertArrayHasKey('refresh_token', $pair);
        $this->assertArrayHasKey('token_type', $pair);
        $this->assertArrayHasKey('expires_in', $pair);
        $this->assertEquals('Bearer', $pair['token_type']);
        $this->assertIsInt($pair['expires_in']);
    }

    public function test_access_token_has_correct_ability(): void
    {
        $user = User::factory()->create();

        $token = $this->service->createAccessToken($user);

        $this->assertContains('access', $token->accessToken->abilities);
    }

    public function test_refresh_token_has_correct_ability(): void
    {
        $user = User::factory()->create();

        $token = $this->service->createRefreshToken($user);

        $this->assertContains('refresh', $token->accessToken->abilities);
    }

    public function test_revokes_all_user_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('test1');
        $user->createToken('test2');

        $this->service->revokeAllUserTokens($user);

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_extracts_token_id(): void
    {
        $result = AuthService::extractTokenId('42|abc123def456');

        $this->assertEquals('42', $result);
    }

    public function test_returns_null_for_invalid_token_id(): void
    {
        $result = AuthService::extractTokenId('no-pipe-here');

        $this->assertEquals('no-pipe-here', $result);
    }

    public function test_token_ttl_matches_constants(): void
    {
        $this->assertEquals(15, AuthService::ACCESS_TOKEN_TTL);
        $this->assertEquals(10080, AuthService::REFRESH_TOKEN_TTL);
    }
}
