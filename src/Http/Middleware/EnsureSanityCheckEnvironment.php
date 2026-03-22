<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class EnsureSanityCheckEnvironment
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! (bool) config('sanity-check.enabled', true)) {
            throw new NotFoundHttpException;
        }

        if (app()->environment('production') && ! (bool) config('sanity-check.allow_in_production', false)) {
            throw new NotFoundHttpException;
        }

        $list = array_values(array_filter((array) config('sanity-check.environment_allowlist', [])));
        if ($list !== [] && ! in_array((string) config('app.env'), $list, true)) {
            throw new NotFoundHttpException;
        }

        return $next($request);
    }
}
