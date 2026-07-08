<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;
use Illuminate\Support\Str;

class AuthService
{
    const ACCESS_TOKEN_ABILITY = 'access';
    const REFRESH_TOKEN_ABILITY = 'refresh';
    const ACCESS_TOKEN_TTL = 15; // minutes
    const REFRESH_TOKEN_TTL = 10080; // minutes (7 days)

    public function createTokenPair(User $user, string $deviceName = 'default'): array
    {
        $accessToken = $this->createAccessToken($user, $deviceName);
        $refreshToken = $this->createRefreshToken($user, $deviceName);

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_TTL * 60,
        ];
    }

    public function createAccessToken(User $user, string $deviceName = 'default'): NewAccessToken
    {
        $token = $user->createToken($this->buildTokenName($deviceName, 'access'), [self::ACCESS_TOKEN_ABILITY]);
        $token->accessToken->expires_at = now()->addMinutes(self::ACCESS_TOKEN_TTL);
        $token->accessToken->save();
        return $token;
    }

    public function createRefreshToken(User $user, string $deviceName = 'default'): NewAccessToken
    {
        $token = $user->createToken($this->buildTokenName($deviceName, 'refresh'), [self::REFRESH_TOKEN_ABILITY]);
        $token->accessToken->expires_at = now()->addMinutes(self::REFRESH_TOKEN_TTL);
        $token->accessToken->save();
        return $token;
    }

    public function revokeAllUserTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    private function buildTokenName(string $deviceName, string $type): string
    {
        return $deviceName . '_' . $type . '_' . Str::random(6);
    }

    public static function extractTokenId(string $tokenId): ?string
    {
        $parts = explode('|', $tokenId, 2);
        return $parts[0] ?? null;
    }
}
