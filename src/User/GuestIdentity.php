<?php
namespace Yiisoft\Yii\Web\User;

class GuestIdentity implements IdentityInterface
{
    public function getId(): ?string
    {
        return null;
    }
}
