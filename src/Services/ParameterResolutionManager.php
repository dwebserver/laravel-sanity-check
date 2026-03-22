<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Services;

use DynamicWeb\SanityCheck\Contracts\ParameterResolverInterface;
use DynamicWeb\SanityCheck\DTOs\RouteCandidateData;

/**
 * Resolves all dynamic segments for a candidate using an ordered resolver chain.
 */
final class ParameterResolutionManager
{
    /**
     * @param  list<ParameterResolverInterface>  $chain
     */
    public function __construct(
        private readonly array $chain,
    ) {
    }

    /**
     * @return array<string, string>|null Null when any required parameter stays unresolved.
     */
    public function resolveAll(RouteCandidateData $candidate): ?array
    {
        $params = [];

        foreach ($candidate->parameterNames as $name) {
            $value = null;

            foreach ($this->chain as $resolver) {
                $value = $resolver->resolve($name, $candidate->route);
                if ($value !== null) {
                    break;
                }
            }

            if ($value === null) {
                return null;
            }

            $params[$name] = $value;
        }

        return $params;
    }
}
