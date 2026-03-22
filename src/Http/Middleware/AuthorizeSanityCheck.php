<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces {@see config('sanity-check.authorization_ability')} when set.
 * Returns a plain HTML 403 view for browsers and JSON for API clients.
 */
final class AuthorizeSanityCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        $ability = config('sanity-check.authorization_ability');
        if (! is_string($ability) || $ability === '') {
            return $next($request);
        }

        $user = $request->user();
        if ($user !== null && $user->can($ability)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'You do not have permission to access the sanity check.'], 403);
        }

        return response()
            ->view('sanity-check::errors.forbidden', [
                'ability' => $ability,
            ], 403)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
