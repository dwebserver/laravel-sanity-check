<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Services;

use Closure;
use DynamicWeb\SanityCheck\DTOs\RouteCandidateData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Applies package include/exclude rules and structural skips to raw candidates.
 */
final class RouteFilter
{
    /**
     * @param  list<string>  $routeNamePrefixes
     * @param  list<string>  $routeUriPrefixes
     * @param  list<string>  $includeRouteNames
     * @param  list<string>  $includePatterns
     * @param  list<string>  $excludeRouteNames
     * @param  list<string>  $excludePatterns
     * @param  list<string>  $parameterResolverKeys
     */
    public function __construct(
        private readonly array $routeNamePrefixes,
        private readonly array $routeUriPrefixes,
        private readonly array $includeRouteNames,
        private readonly array $includePatterns,
        private readonly array $excludeRouteNames,
        private readonly array $excludePatterns,
        private readonly bool $skipSignedRoutes,
        private readonly bool $skipThrottledRoutes,
        private readonly bool $skipClosureRoutes,
        private readonly bool $skipVendorRoutes,
        private readonly bool $ignoreMissingBoundModels,
        private readonly array $parameterResolverKeys,
        private readonly int $maxRoutesPerRun,
    ) {
    }

    /**
     * @param  Collection<int, RouteCandidateData>  $candidates
     * @return Collection<int, RouteCandidateData>
     */
    public function filter(Collection $candidates): Collection
    {
        $out = new Collection;

        foreach ($candidates as $candidate) {
            if ($this->shouldReject($candidate)) {
                continue;
            }

            $out->push($candidate);

            if ($this->maxRoutesPerRun <= $out->count()) {
                break;
            }
        }

        return $out;
    }

    private function shouldReject(RouteCandidateData $candidate): bool
    {
        $route = $candidate->route;

        if ($this->skipClosureRoutes && $route->getAction('uses') instanceof Closure) {
            return true;
        }

        if ($this->skipVendorRoutes && $this->controllerDefinedInVendor($route)) {
            return true;
        }

        if ($this->skipSignedRoutes && $this->usesMiddlewareKeyword($route, ['signed', 'validatesignature'])) {
            return true;
        }

        if ($this->skipThrottledRoutes && $this->usesMiddlewareKeyword($route, ['throttle'])) {
            return true;
        }

        if ($this->ignoreMissingBoundModels && $this->hasImplicitModelBindingWithoutResolver($route)) {
            return true;
        }

        $name = $route->getName() ?? '';
        $uri = '/'.ltrim($route->uri(), '/');

        foreach ($this->excludeRouteNames as $ex) {
            if ($ex !== '' && $name === $ex) {
                return true;
            }
        }

        foreach ($this->excludePatterns as $pattern) {
            if ($pattern !== '' && (Str::is($pattern, $name) || Str::is($pattern, $uri))) {
                return true;
            }
        }

        if ($this->includeRouteNames !== []) {
            if ($name === '' || ! in_array($name, $this->includeRouteNames, true)) {
                return true;
            }
        }

        if ($this->includePatterns !== []) {
            $match = false;
            foreach ($this->includePatterns as $pattern) {
                if ($pattern !== '' && (Str::is($pattern, $name) || Str::is($pattern, $uri))) {
                    $match = true;
                    break;
                }
            }
            if (! $match) {
                return true;
            }
        }

        if ($this->routeNamePrefixes !== []) {
            if ($name === '') {
                return true;
            }
            $ok = false;
            foreach ($this->routeNamePrefixes as $prefix) {
                if ($prefix !== '' && Str::startsWith($name, $prefix)) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok) {
                return true;
            }
        }

        if ($this->routeUriPrefixes !== []) {
            $ok = false;
            foreach ($this->routeUriPrefixes as $prefix) {
                $normalized = ($prefix !== '' && str_starts_with($prefix, '/')) ? $prefix : '/'.ltrim((string) $prefix, '/');
                if ($prefix !== '' && Str::startsWith($uri, $normalized)) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $needles
     */
    private function usesMiddlewareKeyword(Route $route, array $needles): bool
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            $m = strtolower((string) $middleware);
            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($m, strtolower($needle))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function controllerDefinedInVendor(Route $route): bool
    {
        $uses = $route->getAction('uses');
        if (! is_string($uses)) {
            return false;
        }

        $class = null;
        if (str_contains($uses, '@')) {
            $class = explode('@', $uses, 2)[0];
        } elseif (class_exists($uses)) {
            $class = $uses;
        }

        if ($class === null || ! class_exists($class)) {
            return false;
        }

        try {
            $file = (new \ReflectionClass($class))->getFileName();
        } catch (ReflectionException) {
            return false;
        }

        if ($file === false) {
            return false;
        }

        return str_contains($file, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR);
    }

    private function hasImplicitModelBindingWithoutResolver(Route $route): bool
    {
        $uses = $route->getAction('uses');
        if ($uses instanceof Closure) {
            return false;
        }

        $class = null;
        $method = null;

        if (is_string($uses)) {
            if (str_contains($uses, '@')) {
                [$class, $method] = explode('@', $uses, 2);
            } elseif (class_exists($uses)) {
                $class = $uses;
                $method = '__invoke';
            }
        }

        if ($class === null || $method === null || ! class_exists($class) || ! method_exists($class, $method)) {
            return false;
        }

        try {
            $ref = new ReflectionMethod($class, $method);
        } catch (ReflectionException) {
            return false;
        }

        foreach ($ref->getParameters() as $param) {
            $type = $param->getType();
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();
            if (! class_exists($typeName)) {
                continue;
            }

            if (is_subclass_of($typeName, Model::class)) {
                $paramName = $param->getName();
                if (! in_array($paramName, $this->parameterResolverKeys, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
