<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Enums;

enum RunTrigger: string
{
    case Web = 'web';
    case Cli = 'cli';
}
