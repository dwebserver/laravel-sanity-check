<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Http\Controllers;

use DynamicWeb\SanityCheck\Models\SanityCheckRun;
use DynamicWeb\SanityCheck\Support\EphemeralRunCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SanityCheckExportController extends Controller
{
    public function json(Request $request, ?string $uuid = null): JsonResponse
    {
        $payload = $this->resolveExportPayload($request, $uuid);
        if ($payload === null) {
            return response()->json(['message' => 'Run not found.'], 404);
        }

        return response()->json($payload);
    }

    public function csv(Request $request, ?string $uuid = null): StreamedResponse
    {
        $payload = $this->resolveExportPayload($request, $uuid);
        if ($payload === null) {
            abort(404);
        }

        $filename = 'sanity-check-'.preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) ($payload['uuid'] ?? 'export')).'.csv';

        return response()->streamDownload(function () use ($payload): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, [
                'route_name',
                'uri',
                'method',
                'resolved_uri',
                'action',
                'status_code',
                'classification',
                'classification_semantic',
                'response_time_ms',
                'note',
                'ignored_reason',
                'error_message',
            ]);

            foreach ($payload['items'] ?? [] as $row) {
                fputcsv($out, [
                    $row['route_name'] ?? '',
                    $row['uri'] ?? '',
                    $row['method'] ?? '',
                    $row['resolved_uri'] ?? '',
                    $row['action'] ?? '',
                    isset($row['status_code']) ? (string) $row['status_code'] : '',
                    $row['classification'] ?? '',
                    $row['classification_semantic'] ?? '',
                    isset($row['response_time_ms']) ? (string) $row['response_time_ms'] : '',
                    $row['note'] ?? '',
                    $row['ignored_reason'] ?? '',
                    $row['error_message'] ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveExportPayload(Request $request, ?string $uuid): ?array
    {
        $uuid = $uuid ?? (string) $request->query('uuid', '');

        if ($uuid === '') {
            $run = SanityCheckRun::with('items')->orderByDesc('id')->first();
            if (! $run instanceof SanityCheckRun) {
                return null;
            }

            return $this->runModelToExportArray($run);
        }

        $model = SanityCheckRun::with('items')->where('uuid', $uuid)->first();
        if ($model instanceof SanityCheckRun) {
            return $this->runModelToExportArray($model);
        }

        $cached = Cache::get(EphemeralRunCache::key($uuid));
        if (is_array($cached)) {
            return $this->ephemeralExportPayload($cached);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function runModelToExportArray(SanityCheckRun $run): array
    {
        return [
            'uuid' => $run->uuid,
            'environment' => $run->environment,
            'trigger' => $run->trigger,
            'triggered_by_user_id' => $run->triggered_by_user_id,
            'executed_by_id' => $run->executed_by_id,
            'executed_by_type' => $run->executed_by_type,
            'duration_ms' => $run->duration_ms,
            'total_routes' => $run->total_routes,
            'tested_routes' => $run->tested_routes,
            'ignored_routes' => $run->ignored_routes,
            'success_count' => $run->success_count,
            'redirect_count' => $run->redirect_count,
            'client_error_count' => $run->client_error_count,
            'server_error_count' => $run->server_error_count,
            'success_rate' => (float) $run->success_rate,
            'counts' => $run->counts,
            'rates_percent' => $run->rates_percent,
            'started_at' => $run->started_at?->toIso8601String(),
            'finished_at' => $run->finished_at?->toIso8601String(),
            'created_at' => $run->created_at?->toIso8601String(),
            'items' => $run->items->map(static function ($i): array {
                $semantic = (string) ($i->getAttributes()['classification'] ?? '');

                return [
                    'route_name' => $i->route_name,
                    'uri' => $i->uri,
                    'method' => $i->method,
                    'resolved_uri' => $i->resolved_uri,
                    'action' => $i->action,
                    'status_code' => $i->status_code,
                    'classification' => $i->legacy_classification_bucket,
                    'classification_semantic' => $semantic,
                    'response_time_ms' => $i->response_time_ms,
                    'note' => $i->note,
                    'is_ignored' => $i->is_ignored,
                    'parameters' => $i->parameters,
                    'ignored_reason' => data_get($i->meta, 'ignored_reason'),
                    'error_message' => data_get($i->meta, 'error_message'),
                ];
            })->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $cached
     * @return array<string, mixed>
     */
    private function ephemeralExportPayload(array $cached): array
    {
        $toIso = static function (mixed $ts): ?string {
            if (! is_numeric($ts)) {
                return null;
            }

            return Carbon::createFromTimestamp((int) floor((float) $ts))->toIso8601String();
        };

        $items = [];
        foreach ($cached['items'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $legacy = (string) ($row['classification'] ?? '');
            $items[] = [
                'route_name' => $row['route_name'] ?? null,
                'uri' => $row['uri'] ?? '',
                'method' => $row['method'] ?? '',
                'resolved_uri' => $row['resolved_uri'] ?? null,
                'action' => $row['action'] ?? null,
                'status_code' => $row['status_code'] ?? null,
                'classification' => $legacy,
                'classification_semantic' => $row['classification_semantic'] ?? null,
                'response_time_ms' => $row['response_time_ms'] ?? null,
                'note' => $row['note'] ?? null,
                'is_ignored' => $row['is_ignored'] ?? null,
                'parameters' => $row['parameters'] ?? null,
                'ignored_reason' => $row['ignored_reason'] ?? null,
                'error_message' => $row['error_message'] ?? null,
            ];
        }

        return [
            'uuid' => (string) ($cached['uuid'] ?? ''),
            'environment' => (string) ($cached['environment'] ?? ''),
            'trigger' => (string) ($cached['trigger'] ?? ''),
            'triggered_by_user_id' => $cached['triggered_by_user_id'] ?? null,
            'executed_by_id' => $cached['executed_by_id'] ?? null,
            'executed_by_type' => $cached['executed_by_type'] ?? null,
            'duration_ms' => $cached['duration_ms'] ?? null,
            'total_routes' => $cached['total_routes'] ?? null,
            'tested_routes' => $cached['tested_routes'] ?? null,
            'ignored_routes' => $cached['ignored_routes'] ?? null,
            'success_count' => $cached['success_count'] ?? null,
            'redirect_count' => $cached['redirect_count'] ?? null,
            'client_error_count' => $cached['client_error_count'] ?? null,
            'server_error_count' => $cached['server_error_count'] ?? null,
            'success_rate' => $cached['success_rate'] ?? null,
            'counts' => is_array($cached['counts'] ?? null) ? $cached['counts'] : [],
            'rates_percent' => is_array($cached['rates_percent'] ?? null) ? $cached['rates_percent'] : null,
            'started_at' => isset($cached['started_at']) ? $toIso($cached['started_at']) : null,
            'finished_at' => isset($cached['finished_at']) ? $toIso($cached['finished_at']) : null,
            'created_at' => null,
            'items' => $items,
        ];
    }
}
