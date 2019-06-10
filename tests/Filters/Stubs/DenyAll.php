<?php

namespace Yiisoft\Web\Tests\Filters\Stubs;

use Yiisoft\Access\CheckAccessInterface;

class DenyAll implements CheckAccessInterface
{
    public function checkAccess($userId, string $permissionName, array $parameters = []): bool
    {
        return false;
    }
}
