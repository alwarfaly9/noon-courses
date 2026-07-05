<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StructuredLogging
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        // Omit health-check and webhook noise
        if (str_starts_with($request->path(), '_')) {
            return $response;
        }

        $userId = optional($request->user())->id;

        Log::channel('api')->info('api_request', [
            'method'     => $request->method(),
            'path'       => '/' . $request->path(),
            'status'     => $response->getStatusCode(),
            'duration_ms'=> $duration,
            'user_id'    => $userId,
            'ip'         => $request->ip(),
        ]);

        return $response;
    }
}
