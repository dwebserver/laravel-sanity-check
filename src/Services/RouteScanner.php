<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Services;

use DynamicWeb\SanityCheck\Contracts\RouteScannerInterface;
use DynamicWeb\SanityCheck\DTOs\RouteCandidateData;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;

/**
 * Enumerates route/method pairs that match configured HTTP verbs.
 */
final class RouteScanner implements RouteScannerInterface
{
    /**
     * @param  list<string>  $allowedMethods
     */
    public function __construct(
        private readonly Router $router,
        private readonly array $allowedMethods,
    ) {
    }

    public function discover(): Collection
    {
        $allowed = array_map('strtoupper', $this->allowedMethods);
        $candidates = new Collection;

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            if (! $route instanceof Route) {
                continue;
            }

            foreach ($route->methods() as $method) {
                $method = strtoupper((string) $method);
                if (in_array($method, ['HEAD'], true) && in_array('GET', $allowed, true)) {
                    if (in_array('GET', $route->methods(), true)) {
                        continue;
                    }
                }

                if (! in_array($method, $allowed, true)) {
                    continue;
                }

                $candidates->push(new RouteCandidateData(
                    name: $route->getName(),
                    uriTemplate: $route->uri(),
                    method: $method,
                    parameterNames: $route->parameterNames(),
                    route: $route,
                ));
            }
        }

        return $candidates;
    }
}
