<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property int|null $duration_ms
 * @property string|null $executed_by_id
 * @property string|null $executed_by_type
 * @property int $total_routes
 * @property int $tested_routes
 * @property int $ignored_routes
 * @property int $success_count
 * @property int $redirect_count
 * @property int $client_error_count
 * @property int $server_error_count
 * @property float $success_rate
 * @property array<string, mixed>|null $config_snapshot
 * @property array<string, mixed>|null $meta
 * @property-read string $environment
 * @property-read string $trigger
 * @property-read int|null $triggered_by_user_id
 * @property-read array{'2xx': int, '3xx': int, '4xx': int, '5xx': int, ignored: int} $counts
 * @property-read array<string, float>|null $rates_percent
 * @property-read Collection<int, SanityCheckItem> $items
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 */
class SanityCheckRun extends Model
{
    protected $table = 'sanity_check_runs';

    protected $fillable = [
        'uuid',
        'started_at',
        'finished_at',
        'duration_ms',
        'executed_by_id',
        'executed_by_type',
        'total_routes',
        'tested_routes',
        'ignored_routes',
        'success_count',
        'redirect_count',
        'client_error_count',
        'server_error_count',
        'success_rate',
        'config_snapshot',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'success_rate' => 'float',
            'config_snapshot' => 'array',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $run): void {
            if ($run->uuid === null || $run->uuid === '') {
                $run->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return HasMany<SanityCheckItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SanityCheckItem::class, 'run_id');
    }

    protected function environment(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) data_get($this->meta, 'environment', ''),
        );
    }

    protected function trigger(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) data_get($this->meta, 'trigger', ''),
        );
    }

    protected function triggeredByUserId(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                $v = data_get($this->meta, 'triggered_by_user_id');

                return is_int($v) ? $v : null;
            },
        );
    }

    protected function counts(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $defaults = ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0, 'ignored' => 0];
                $c = data_get($this->meta, 'counts');
                if (! is_array($c)) {
                    return $defaults;
                }

                return array_merge($defaults, array_intersect_key($c, $defaults));
            },
        );
    }

    protected function ratesPercent(): Attribute
    {
        return Attribute::make(
            get: function (): ?array {
                $r = data_get($this->meta, 'rates_percent');

                return is_array($r) ? $r : null;
            },
        );
    }
}
