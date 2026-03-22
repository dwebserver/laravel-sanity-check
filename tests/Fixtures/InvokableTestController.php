<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Fixtures;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Non-closure controller action for filter tests (lives under tests/, not vendor).
 */
final class InvokableTestController extends Controller
{
    public function __invoke(): Response
    {
        return response('invokable-fixture', 200);
    }
}
