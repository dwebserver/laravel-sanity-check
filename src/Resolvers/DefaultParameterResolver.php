<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Resolvers;

use DynamicWeb\SanityCheck\Contracts\ParameterResolverInterface;
use Illuminate\Routing\Route;

/**
 * Final fallback: placeholder substitution when `default_parameter_strategy` allows it.
 */
final class DefaultParameterResolver implements ParameterResolverInterface
{
    public function __construct(
        private readonly string $strategy,
        private readonly string $placeholder,
    ) {
    }

    public function resolve(string $parameterName, Route $route): ?string
    {
        return $this->strategy === 'placeholder' ? $this->placeholder : null;
    }
}
