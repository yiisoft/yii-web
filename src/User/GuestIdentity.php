<?php
namespace Yiisoft\Yii\Web\User;

use Yiisoft\Auth\IdentityInterface;

class GuestIdentity implements IdentityInterface
{
    public function getId(): ?string
    {
        return null;
    }
}
