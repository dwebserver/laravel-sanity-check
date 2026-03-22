<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Models;

use DynamicWeb\SanityCheck\Enums\OutcomeClassification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $run_id
 * @property string|null $route_name
 * @property string $method
 * @property string $uri
 * @property string|null $resolved_uri
 * @property string|null $action
 * @property int|null $status_code
 * @property string $classification
 * @property int|null $response_time_ms
 * @property string|null $note
 * @property bool $is_ignored
 * @property array<string, mixed>|null $parameters
 * @property array<string, mixed>|null $meta
 * @property-read string $legacy_classification_bucket
 */
class SanityCheckItem extends Model
{
    protected $table = 'sanity_check_items';

    protected $fillable = [
        'run_id',
        'route_name',
        'method',
        'uri',
        'resolved_uri',
        'action',
        'status_code',
        'classification',
        'response_time_ms',
        'note',
        'is_ignored',
        'parameters',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_ignored' => 'boolean',
            'parameters' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<SanityCheckRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(SanityCheckRun::class, 'run_id');
    }

    protected function legacyClassificationBucket(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $raw = (string) ($this->attributes['classification'] ?? '');
                $enum = OutcomeClassification::tryFrom($raw);
                if ($enum !== null) {
                    return $enum->toLegacyBucket();
                }

                if (in_array($raw, ['2xx', '3xx', '4xx', '5xx', 'ignored'], true)) {
                    return $raw;
                }

                return '4xx';
            },
        );
    }
}
