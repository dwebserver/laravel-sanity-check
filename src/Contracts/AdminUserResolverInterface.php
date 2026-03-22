<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface AdminUserResolverInterface
{
    public function resolve(): ?Authenticatable;
}
