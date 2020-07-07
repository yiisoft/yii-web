<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\User;

use Yiisoft\Auth\IdentityInterface;

final class GuestIdentity implements IdentityInterface
{
    public function getId(): ?string
    {
        return null;
    }
}
