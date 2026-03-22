<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Resolvers;

use DynamicWeb\SanityCheck\Contracts\ParameterResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Supplies primary keys for implicit Eloquent bindings inferred from controller actions.
 */
final class RouteModelBindingParameterResolver implements ParameterResolverInterface
{
    public function resolve(string $parameterName, Route $route): ?string
    {
        $modelClass = $this->modelClassForParameter($route, $parameterName);
        if ($modelClass === null || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $builder = $modelClass::query();

        $model = $builder->orderBy($builder->getModel()->getKeyName())->first();
        if (! $model instanceof Model) {
            return null;
        }

        return (string) $model->getKey();
    }

    private function modelClassForParameter(Route $route, string $parameterName): ?string
    {
        $uses = $route->getAction('uses');
        if ($uses instanceof \Closure) {
            return null;
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
            return null;
        }

        try {
            $ref = new ReflectionMethod($class, $method);
        } catch (ReflectionException) {
            return null;
        }

        foreach ($ref->getParameters() as $param) {
            if ($parameterName !== $param->getName()) {
                continue;
            }

            $type = $param->getType();
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                return null;
            }

            $typeName = $type->getName();
            if (class_exists($typeName) && is_subclass_of($typeName, Model::class)) {
                return $typeName;
            }

            return null;
        }

        return null;
    }
}
