<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\DTOs;

use Illuminate\Routing\Route;

/**
 * Immutable description of a single HTTP verb against a registered route.
 */
final class RouteCandidateData
{
    /**
     * @param  list<string>  $parameterNames
     */
    public function __construct(
        public readonly ?string $name,
        public readonly string $uriTemplate,
        public readonly string $method,
        public readonly array $parameterNames,
        public readonly Route $route,
    ) {
    }
}
