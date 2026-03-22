<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Unit;

use DynamicWeb\SanityCheck\Models\SanityCheckRun;
use DynamicWeb\SanityCheck\Services\SanityCheckRetentionService;
use DynamicWeb\SanityCheck\Tests\TestCase;
use Illuminate\Support\Carbon;

final class SanityCheckRetentionServiceTest extends TestCase
{
    public function test_prune_deletes_runs_older_than_retention_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-15 12:00:00'));

        $this->createBareRun('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', now()->subDays(20));
        $this->createBareRun('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', now()->subDays(2));

        $service = new SanityCheckRetentionService(retentionDays: 10, maxSavedRuns: 0);
        $service->prune();

        $this->assertSame(1, SanityCheckRun::query()->count());
        $this->assertSame('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', SanityCheckRun::query()->value('uuid'));

        Carbon::setTestNow();
    }

    public function test_prune_trims_to_max_saved_runs_newest_first(): void
    {
        $this->createBareRun('11111111-1111-4111-8111-111111111111', now());
        $this->createBareRun('22222222-2222-4222-8222-222222222222', now());
        $this->createBareRun('33333333-3333-4333-8333-333333333333', now());

        $service = new SanityCheckRetentionService(retentionDays: 0, maxSavedRuns: 2);
        $service->prune();

        $uuids = SanityCheckRun::query()->orderBy('id')->pluck('uuid')->all();
        $this->assertCount(2, $uuids);
        $this->assertContains('22222222-2222-4222-8222-222222222222', $uuids);
        $this->assertContains('33333333-3333-4333-8333-333333333333', $uuids);
    }

    private function createBareRun(string $uuid, Carbon $createdAt): void
    {
        SanityCheckRun::query()->create([
            'uuid' => $uuid,
            'started_at' => $createdAt,
            'finished_at' => $createdAt,
            'duration_ms' => 1,
            'meta' => ['environment' => 'testing', 'trigger' => 'test', 'counts' => [
                '2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0, 'ignored' => 0,
            ]],
        ]);

        SanityCheckRun::query()->where('uuid', $uuid)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
