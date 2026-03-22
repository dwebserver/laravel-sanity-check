<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Resolvers;

use DynamicWeb\SanityCheck\Contracts\ParameterResolverInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Route;
use InvalidArgumentException;

/**
 * Delegates to entries in `sanity-check.parameter_resolvers` (class or callable).
 */
final class ConfigMappedModelResolver implements ParameterResolverInterface
{
    /**
     * @param  array<string, callable|class-string<ParameterResolverInterface>>  $map
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $map,
    ) {
    }

    public function resolve(string $parameterName, Route $route): ?string
    {
        if (! array_key_exists($parameterName, $this->map)) {
            return null;
        }

        $entry = $this->map[$parameterName];

        if (is_string($entry) && is_subclass_of($entry, ParameterResolverInterface::class)) {
            /** @var ParameterResolverInterface $resolver */
            $resolver = $this->container->make($entry);

            return $resolver->resolve($parameterName, $route);
        }

        if (is_callable($entry)) {
            $value = $entry($parameterName, $route);

            return $value === null ? null : (string) $value;
        }

        throw new InvalidArgumentException('Invalid parameter resolver entry for ['.$parameterName.'].');
    }
}
