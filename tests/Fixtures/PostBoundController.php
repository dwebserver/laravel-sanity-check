<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Fixtures;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route uses implicit binding for {@see StubPost} without a sanity-check resolver entry.
 */
final class PostBoundController extends Controller
{
    public function show(StubPost $post): Response
    {
        return response('post:'.$post->getKey(), 200);
    }
}
