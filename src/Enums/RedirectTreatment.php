<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Enums;

enum RedirectTreatment: string
{
    case Reported = 'reported';
    case Success = 'success';
    case Ignored = 'ignored';
}
