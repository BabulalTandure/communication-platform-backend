<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $userId = $request->user()?->id ?? 'guest';
        $method = $request->method();
        $path = $request->path();
        $status = $response->getStatusCode();

        Log::info("API [{$method}] /{$path} | Status: {$status} | User: {$userId} | Time: {$duration}ms");

        return $response;
    }
}
