<?php

namespace Yiisoft\Web\Tests\Filters\Stubs;

use Yiisoft\Access\CheckAccessInterface;

class AccessChecker implements CheckAccessInterface
{
    private $_permissions = [];

    public function addPermissions($userId, array $permissions)
    {
        foreach ($permissions as $key => $value) {
            if (is_string($value)) {
                $this->_permissions[$userId][$value] = true;
            }

            $this->_permissions[$userId][$key] = $value;
        }
    }

    public function checkAccess($userId, string $permissionName, array $parameters = []): bool
    {
        $condition = $this->_permissions[$userId][$permissionName] ?? false;
        if (is_bool($condition)) {
            return $condition;
        }

        if (is_callable($condition)) {
            return $condition($userId, $parameters);
        }

        return false;
    }
}
