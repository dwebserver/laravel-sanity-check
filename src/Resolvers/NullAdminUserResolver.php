<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Resolvers;

use DynamicWeb\SanityCheck\Contracts\AdminUserResolverInterface;
use Illuminate\Contracts\Auth\Authenticatable;

final class NullAdminUserResolver implements AdminUserResolverInterface
{
    public function resolve(): ?Authenticatable
    {
        return null;
    }
}
