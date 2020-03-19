<?php

namespace Yiisoft\Yii\Web\User\Event;

use Yiisoft\Auth\IdentityInterface;

class AfterLogin
{
    private IdentityInterface $identity;
    private int $duration;

    public function __construct(IdentityInterface $identity, int $duration)
    {
        $this->identity = $identity;
        $this->duration = $duration;
    }

    public function getIdentity(): IdentityInterface
    {
        return $this->identity;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }
}
