<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Contracts;

use DynamicWeb\SanityCheck\DTOs\RouteCandidateData;
use DynamicWeb\SanityCheck\Services\RouteFilter;
use Illuminate\Support\Collection;

interface RouteScannerInterface
{
    /**
     * Discover route/method candidates from the application router.
     *
     * Implementations should only apply HTTP verb filtering (and HEAD de-duplication),
     * leaving include/exclude rules to {@see RouteFilter}.
     *
     * @return Collection<int, RouteCandidateData>
     */
    public function discover(): Collection;
}
