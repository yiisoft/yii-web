<?php
namespace yii\web\tests\filters\stubs;

use Yiisoft\Access\CheckAccessInterface;

class AccessChecker implements CheckAccessInterface
{
    private $permissions = [];

    public function addPermissions($userId, array $permissions)
    {
        foreach ($permissions as $key => $value) {
            if (is_string($value)) {
                $this->permissions[$userId][$value] = true;
            }

            $this->permissions[$userId][$key] = $value;
        }
    }

    public function checkAccess($userId, string $permissionName, array $parameters = []): bool
    {
        $condition = $this->permissions[$userId][$permissionName] ?? false;
        if (is_bool($condition)) {
            return $condition;
        }

        if (is_callable($condition)) {
            return $condition($userId, $parameters);
        }

        return false;
    }
}
