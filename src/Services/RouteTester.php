<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Services;

use DynamicWeb\SanityCheck\Contracts\AdminUserResolverInterface;
use DynamicWeb\SanityCheck\Contracts\RouteTesterInterface;
use DynamicWeb\SanityCheck\DTOs\RouteCandidateData;
use DynamicWeb\SanityCheck\DTOs\RouteExecutionResult;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Executes a candidate request and captures timing. Supports internal kernel
 * dispatch and optional HTTP client mode for parity with package configuration.
 */
final class RouteTester implements RouteTesterInterface
{
    public function __construct(
        private readonly Kernel $kernel,
        private readonly UrlGenerator $urlGenerator,
        private readonly AdminUserResolverInterface $adminUserResolver,
        private readonly float $timeoutSeconds,
        private readonly string $executionMode,
        private readonly bool $followRedirects,
    ) {
    }

    public function test(RouteCandidateData $candidate, array $resolvedParameters): RouteExecutionResult
    {
        try {
            $path = $this->buildPath($candidate, $resolvedParameters);
        } catch (Throwable $e) {
            return new RouteExecutionResult(
                resolvedPath: null,
                statusCode: null,
                responseTimeMs: null,
                rawErrorMessage: $e->getMessage(),
            );
        }

        return strtolower($this->executionMode) === 'http'
            ? $this->executeViaHttp($candidate, $path)
            : $this->executeViaKernel($candidate, $path);
    }

    /**
     * @param  array<string, string>  $params
     */
    private function buildPath(RouteCandidateData $candidate, array $params): string
    {
        if ($candidate->name !== null && $candidate->name !== '') {
            return $this->urlGenerator->route($candidate->name, $params, false);
        }

        $uri = $candidate->uriTemplate;
        foreach ($params as $key => $value) {
            $uri = str_replace(
                ['{'.$key.'}', '{'.$key.'?}'],
                [(string) $value, (string) $value],
                $uri
            );
        }

        return '/'.ltrim($uri, '/');
    }

    private function executeViaKernel(RouteCandidateData $candidate, string $path): RouteExecutionResult
    {
        $started = (int) (microtime(true) * 1000);

        try {
            if ($this->timeoutSeconds > 0) {
                @set_time_limit((int) ceil($this->timeoutSeconds));
            }

            $request = Request::create($path, $candidate->method);
            $request->headers->set('Accept', 'text/html,application/json,*/*');

            $user = $this->adminUserResolver->resolve();
            if ($user !== null) {
                $request->setUserResolver(static fn () => $user);
            }

            $response = $this->kernel->handle($request);
            $elapsed = (int) (microtime(true) * 1000) - $started;
            $status = $response->getStatusCode();
            $this->kernel->terminate($request, $response);

            return new RouteExecutionResult(
                resolvedPath: $path,
                statusCode: $status,
                responseTimeMs: $elapsed,
                rawErrorMessage: null,
            );
        } catch (Throwable $e) {
            $elapsed = (int) (microtime(true) * 1000) - $started;

            return new RouteExecutionResult(
                resolvedPath: $path,
                statusCode: null,
                responseTimeMs: $elapsed,
                rawErrorMessage: $e->getMessage(),
            );
        }
    }

    private function executeViaHttp(RouteCandidateData $candidate, string $path): RouteExecutionResult
    {
        $base = rtrim((string) config('app.url'), '/');
        $url = $base.'/'.ltrim($path, '/');
        $started = (int) (microtime(true) * 1000);

        try {
            $timeout = max(1, (int) ceil($this->timeoutSeconds));
            $pending = Http::timeout($timeout)
                ->withOptions(['allow_redirects' => $this->followRedirects]);

            $response = $pending->send($candidate->method, $url);
            $elapsed = (int) (microtime(true) * 1000) - $started;

            return new RouteExecutionResult(
                resolvedPath: $path,
                statusCode: $response->status(),
                responseTimeMs: $elapsed,
                rawErrorMessage: null,
            );
        } catch (Throwable $e) {
            $elapsed = (int) (microtime(true) * 1000) - $started;

            return new RouteExecutionResult(
                resolvedPath: $path,
                statusCode: null,
                responseTimeMs: $elapsed,
                rawErrorMessage: $e->getMessage(),
            );
        }
    }
}
