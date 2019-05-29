<?php
namespace yii\web\tests\filters\stubs;

use Yiisoft\Access\CheckAccessInterface;

class DenyAll implements CheckAccessInterface
{
    public function checkAccess($userId, string $permissionName, array $parameters = []): bool
    {
        return false;
    }
}