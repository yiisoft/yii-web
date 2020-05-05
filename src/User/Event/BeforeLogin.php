<?php

namespace Yiisoft\Yii\Web\User\Event;

use Yiisoft\Auth\IdentityInterface;

class BeforeLogin
{
    private IdentityInterface $identity;
    private bool $isValid = true;

    public function __construct(IdentityInterface $identity)
    {
        $this->identity = $identity;
    }

    public function invalidate(): void
    {
        $this->isValid = false;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getIdentity(): IdentityInterface
    {
        return $this->identity;
    }
}
