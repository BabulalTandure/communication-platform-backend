<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->isActive()) {
            // Revoke all tokens for disabled user
            $user->tokens()->delete();

            return $this->errorResponse('Account is disabled. Please contact the administrator.', null, 403);
        }

        return $next($request);
    }
}
