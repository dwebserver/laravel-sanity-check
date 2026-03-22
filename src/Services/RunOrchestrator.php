<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Services;

use DynamicWeb\SanityCheck\Contracts\ResultRepositoryInterface;
use DynamicWeb\SanityCheck\Contracts\RouteScannerInterface;
use DynamicWeb\SanityCheck\Contracts\RouteTesterInterface;
use DynamicWeb\SanityCheck\DTOs\CheckResultData;
use DynamicWeb\SanityCheck\DTOs\RouteCandidateData;
use DynamicWeb\SanityCheck\DTOs\RunContextData;
use DynamicWeb\SanityCheck\DTOs\RunExecutionBundle;
use DynamicWeb\SanityCheck\DTOs\RunOrchestratorOptions;
use DynamicWeb\SanityCheck\Enums\OutcomeClassification;
use DynamicWeb\SanityCheck\Enums\RunTrigger;
use DynamicWeb\SanityCheck\Support\ErrorMessageSanitizer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Coordinates discovery, filtering, resolution, execution, classification, and persistence.
 */
final class RunOrchestrator
{
    public function __construct(
        private readonly RouteScannerInterface $scanner,
        private readonly RouteFilter $routeFilter,
        private readonly ParameterResolutionManager $parameterResolution,
        private readonly RouteTesterInterface $routeTester,
        private readonly ResponseClassifier $classifier,
        private readonly ResultAggregator $aggregator,
        private readonly ResultRepositoryInterface $resultRepository,
        private readonly bool $saveRuns,
        private readonly bool $ignoreUnresolvableRoutes,
    ) {
    }

    public function run(RunTrigger $trigger, ?Authenticatable $user = null, ?RunOrchestratorOptions $options = null): RunExecutionBundle
    {
        $started = microtime(true);
        $uuid = (string) Str::uuid();

        $executedById = null;
        $executedByType = null;
        $triggeredByUserId = null;
        if ($user !== null) {
            $id = $user->getAuthIdentifier();
            $executedById = $id !== null ? (string) $id : null;
            $executedByType = $user::class;
            $triggeredByUserId = match (true) {
                is_int($id) => $id,
                is_string($id) && ctype_digit($id) => (int) $id,
                default => null,
            };
        }

        $persist = $this->saveRuns;
        if ($options !== null && $options->persist !== null) {
            $persist = $options->persist;
        }

        $context = new RunContextData(
            environment: (string) config('app.env'),
            trigger: $trigger->value,
            triggeredByUserId: $triggeredByUserId,
            executedById: $executedById,
            executedByType: $executedByType,
            saveRuns: $persist,
            ignoreUnresolvableRoutes: $this->ignoreUnresolvableRoutes,
            debug: (bool) config('app.debug'),
        );

        $raw = $this->scanner->discover();
        $candidates = $this->routeFilter->filter($raw);
        $candidates = $this->applyCliRouteSelection($candidates, $options);

        $items = [];
        foreach ($candidates as $candidate) {
            $items[] = $this->checkOne($candidate, $context);
        }

        $finished = microtime(true);

        $summary = $this->aggregator->summarize($uuid, $context, $started, $finished, $items);

        if ($persist) {
            $this->resultRepository->persist($summary, $items);
            $this->resultRepository->applyRetention();
        } else {
            $this->resultRepository->storeEphemeral($uuid, $summary, $items);
        }

        return new RunExecutionBundle($summary, $items);
    }

    /**
     * @param  Collection<int, RouteCandidateData>  $candidates
     * @return Collection<int, RouteCandidateData>
     */
    private function applyCliRouteSelection(Collection $candidates, ?RunOrchestratorOptions $options): Collection
    {
        if ($options === null) {
            return $candidates;
        }

        $c = $candidates;

        if ($options->onlyPatterns !== []) {
            $c = $c->filter(fn (RouteCandidateData $candidate): bool => $this->candidateMatchesAnyPattern($candidate, $options->onlyPatterns));
        }

        if ($options->exceptPatterns !== []) {
            $c = $c->reject(fn (RouteCandidateData $candidate): bool => $this->candidateMatchesAnyPattern($candidate, $options->exceptPatterns));
        }

        if ($options->limit !== null) {
            $c = $c->take(max(1, $options->limit));
        }

        return $c->values();
    }

    /**
     * @param  list<string>  $patterns
     */
    private function candidateMatchesAnyPattern(RouteCandidateData $candidate, array $patterns): bool
    {
        $route = $candidate->route;
        $name = $route->getName() ?? '';
        $uri = '/'.ltrim($route->uri(), '/');

        foreach ($patterns as $pattern) {
            if ($pattern !== '' && (Str::is($pattern, $name) || Str::is($pattern, $uri))) {
                return true;
            }
        }

        return false;
    }

    private function checkOne(RouteCandidateData $candidate, RunContextData $context): CheckResultData
    {
        $action = $this->resolveActionName($candidate);

        $resolved = $this->parameterResolution->resolveAll($candidate);

        if ($resolved === null) {
            if ($context->ignoreUnresolvableRoutes) {
                return new CheckResultData(
                    routeName: $candidate->name,
                    uriTemplate: $candidate->uriTemplate,
                    method: $candidate->method,
                    resolvedUri: null,
                    statusCode: null,
                    classification: OutcomeClassification::Ignored,
                    responseTimeMs: null,
                    ignoredReason: 'unresolvable_route_parameter',
                    safeErrorSummary: null,
                    wasExecuted: false,
                    action: $action,
                    resolvedParameters: null,
                );
            }

            return new CheckResultData(
                routeName: $candidate->name,
                uriTemplate: $candidate->uriTemplate,
                method: $candidate->method,
                resolvedUri: null,
                statusCode: null,
                classification: OutcomeClassification::ClientError,
                responseTimeMs: null,
                ignoredReason: null,
                safeErrorSummary: 'Missing parameter resolver for dynamic route segment.',
                wasExecuted: false,
                action: $action,
                resolvedParameters: null,
            );
        }

        $execution = $this->routeTester->test($candidate, $resolved);
        $classification = $this->classifier->classify($execution);
        $ignoredReason = $this->classifier->describeIgnoredReason($execution, $classification);

        $safeError = ErrorMessageSanitizer::summarize($execution->rawErrorMessage, $context->debug);

        return new CheckResultData(
            routeName: $candidate->name,
            uriTemplate: $candidate->uriTemplate,
            method: $candidate->method,
            resolvedUri: $execution->resolvedPath,
            statusCode: $execution->statusCode,
            classification: $classification,
            responseTimeMs: $execution->responseTimeMs,
            ignoredReason: $ignoredReason,
            safeErrorSummary: $safeError,
            wasExecuted: true,
            action: $action,
            resolvedParameters: $resolved,
        );
    }

    private function resolveActionName(RouteCandidateData $candidate): ?string
    {
        $name = $candidate->route->getActionName();

        return $name !== '' ? $name : null;
    }
}
