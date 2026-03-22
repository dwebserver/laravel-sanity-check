<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent stub used only for implicit-route-binding reflection in RouteFilter tests.
 */
final class StubPost extends Model
{
    protected $table = 'stub_posts';

    public $timestamps = false;
}
