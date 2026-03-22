<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Console\Commands;

use DynamicWeb\SanityCheck\DTOs\CheckResultData;
use DynamicWeb\SanityCheck\DTOs\RunOrchestratorOptions;
use DynamicWeb\SanityCheck\DTOs\RunSummaryData;
use DynamicWeb\SanityCheck\Enums\RunTrigger;
use DynamicWeb\SanityCheck\Services\JsonExporter;
use DynamicWeb\SanityCheck\Services\RunOrchestrator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;
use Throwable;

final class RunSanityCheckCommand extends Command
{
    protected $signature = 'sanity-check:run
                            {--json : Print the full run payload as JSON (stdout still respects exit code)}
                            {--no-save : Skip persisting this run (overrides config save_runs for this invocation)}
                            {--only= : Comma-separated Str::is patterns; route name or URI must match at least one}
                            {--except= : Comma-separated Str::is patterns to exclude after config filtering}
                            {--limit= : Maximum routes to check after filters (positive integer)}';

    protected $description = 'Execute a sanity check run against application routes (same engine as the dashboard; no HTTP authorization).';

    public function handle(RunOrchestrator $orchestrator, JsonExporter $jsonExporter): int
    {
        if (! (bool) config('sanity-check.enabled', true)) {
            $this->error('The sanity-check package is disabled (`enabled=false`).');

            return self::FAILURE;
        }

        if (! $this->environmentAllowed()) {
            $this->error('Sanity check is not allowed for this environment (see `allow_in_production` and `environment_allowlist`).');

            return self::FAILURE;
        }

        $limit = $this->parsePositiveIntOption('limit');
        if ($limit === false) {
            return self::FAILURE;
        }

        $options = new RunOrchestratorOptions(
            persist: $this->option('no-save') ? false : null,
            onlyPatterns: $this->parseCommaPatterns($this->option('only')),
            exceptPatterns: $this->parseCommaPatterns($this->option('except')),
            limit: $limit,
        );

        $this->info('Running sanity check…');

        try {
            $bundle = $orchestrator->run(RunTrigger::Cli, null, $options);
        } catch (Throwable $e) {
            report($e);
            $this->error('Sanity check failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $summary = $bundle->summary;

        $exitCode = $summary->serverErrorCount > 0 ? self::FAILURE : self::SUCCESS;

        if ($this->option('json')) {
            $this->line(json_encode(
                $jsonExporter->build($summary, $bundle->items),
                JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
            ));

            return $exitCode;
        }

        $this->renderTextSummary($summary, $bundle->items);

        if ($exitCode !== self::SUCCESS) {
            $this->newLine();
            $this->error('Run finished with server errors (5xx). Exit code '.$exitCode.'.');
        }

        return $exitCode;
    }

    /**
     * @param  list<CheckResultData>  $items
     */
    private function renderTextSummary(RunSummaryData $summary, array $items): void
    {
        $counts = $summary->legacyCountSummary();

        $this->newLine();
        $this->components->twoColumnDetail('Run UUID', $summary->uuid);
        $this->components->twoColumnDetail('Environment', $summary->environment);
        $this->components->twoColumnDetail('Trigger', $summary->trigger);
        $this->components->twoColumnDetail('Duration', number_format($summary->durationMs).' ms');
        $this->components->twoColumnDetail('Routes (total / tested / ignored)', sprintf(
            '%d / %d / %d',
            $summary->totalRoutes,
            $summary->testedRoutes,
            $summary->ignoredRoutes
        ));
        $this->components->twoColumnDetail('Success rate (of tested)', number_format($summary->successRate, 2).'%');

        $this->newLine();
        $this->table(
            ['Bucket', 'Count', '% of total'],
            [
                ['2xx (success)', (string) $counts['2xx'], $this->pct($counts['2xx'], $summary->totalRoutes)],
                ['3xx (redirect)', (string) $counts['3xx'], $this->pct($counts['3xx'], $summary->totalRoutes)],
                ['4xx (client error)', (string) $counts['4xx'], $this->pct($counts['4xx'], $summary->totalRoutes)],
                ['5xx (server error)', (string) $counts['5xx'], $this->pct($counts['5xx'], $summary->totalRoutes)],
                ['Ignored', (string) $counts['ignored'], $this->pct($counts['ignored'], $summary->totalRoutes)],
            ]
        );

        if ($summary->serverErrorCount > 0) {
            $this->newLine();
            $this->warn('One or more routes returned a 5xx response. See the table below.');
        }

        $this->newLine();
        $this->info('Route results');

        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                $item->method,
                $item->routeName ?? '—',
                $item->resolvedUri ?? $item->uriTemplate,
                $item->statusCode === null ? '—' : (string) $item->statusCode,
                $item->classification->toLegacyBucket(),
                $item->ignoredReason ?? $item->safeErrorSummary ?? '—',
            ];
        }

        $table = new Table($this->output);
        $table->setHeaders(['Method', 'Route', 'Resolved', 'Status', 'Bucket', 'Note']);
        $table->setRows($rows);
        $table->render();
    }

    private function pct(int $part, int $whole): string
    {
        if ($whole <= 0) {
            return '0.0';
        }

        return number_format($part / $whole * 100, 1);
    }

    private function environmentAllowed(): bool
    {
        if (app()->environment('production') && ! (bool) config('sanity-check.allow_in_production', false)) {
            return false;
        }

        $list = array_values(array_filter((array) config('sanity-check.environment_allowlist', [])));

        return $list === [] || in_array((string) config('app.env'), $list, true);
    }

    /**
     * @return list<string>
     */
    private function parseCommaPatterns(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn (string $s): bool => $s !== ''));
    }

    private function parsePositiveIntOption(string $name): false|int|null
    {
        $raw = $this->option($name);
        if ($raw === null || $raw === false) {
            return null;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        if (! ctype_digit($raw)) {
            $this->error('Option --'.$name.' must be a positive integer.');

            return false;
        }

        $n = (int) $raw;
        if ($n < 1) {
            $this->error('Option --'.$name.' must be at least 1.');

            return false;
        }

        return $n;
    }
}
