<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Contracts;

use Illuminate\Routing\Route;

interface ParameterResolverInterface
{
    /**
     * Return a scalar string value for the route parameter, or null if unknown.
     */
    public function resolve(string $parameterName, Route $route): ?string;
}
