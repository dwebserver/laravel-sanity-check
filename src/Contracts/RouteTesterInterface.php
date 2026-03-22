<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Contracts;

use DynamicWeb\SanityCheck\DTOs\RouteCandidateData;
use DynamicWeb\SanityCheck\DTOs\RouteExecutionResult;

interface RouteTesterInterface
{
    /**
     * Execute a single candidate with fully resolved path parameters.
     *
     * @param  array<string, string>  $resolvedParameters
     */
    public function test(RouteCandidateData $candidate, array $resolvedParameters): RouteExecutionResult;
}
