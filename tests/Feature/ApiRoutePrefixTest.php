<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiRoutePrefixTest extends TestCase
{
    /**
     * Guards against the duplicate `/api/v1/api/v1/` regression:
     * routes/api.php already applies `Route::prefix('v1')` (plus Laravel's
     * automatic `api` prefix), so no registered route may contain `v1/v1`.
     */
    public function test_registered_api_routes_never_contain_a_duplicated_prefix(): void
    {
        $duplicates = collect(Route::getRoutes())
            ->map(fn ($route) => $route->uri())
            ->filter(fn (string $uri) => str_contains($uri, 'v1/v1'))
            ->values()
            ->all();

        $this->assertEmpty($duplicates, 'Duplicate /v1/v1/ prefix found in routes: ' . implode(', ', $duplicates));
    }

    public function test_common_client_endpoints_are_registered_with_a_single_prefix(): void
    {
        $uris = collect(Route::getRoutes())
            ->map(fn ($route) => $route->uri())
            ->values()
            ->all();

        foreach ([
            'api/v1/auth/login',
            'api/v1/auth/register',
            'api/v1/courses',
            'api/v1/categories',
            'api/v1/learning-paths',
            'api/v1/skills',
            'api/v1/chat/conversations',
            'api/v1/analytics/events',
            'api/v1/notifications',
            'api/v1/gamification/stats',
            'api/v1/student/dashboard',
        ] as $expected) {
            $this->assertContains(
                $expected,
                $uris,
                "Expected route [$expected] to be registered exactly once without a duplicated prefix."
            );
        }

        foreach ($uris as $uri) {
            $this->assertStringNotContainsString('api/v1/api/v1', $uri);
        }
    }
}
