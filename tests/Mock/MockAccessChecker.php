<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Mock;

use Yiisoft\Access\AccessCheckerInterface;

class MockAccessChecker implements AccessCheckerInterface
{
    private bool $userHasPermission;

    public function __construct(bool $userHasPermission)
    {
        $this->userHasPermission = $userHasPermission;
    }

    public function userHasPermission($userId, string $permissionName, array $parameters = []): bool
    {
        return $this->userHasPermission;
    }
}
