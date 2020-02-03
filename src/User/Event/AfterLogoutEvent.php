<?php

namespace Yiisoft\Yii\Web\User\Event;

use Yiisoft\Auth\IdentityInterface;

class AfterLogoutEvent
{
    private $identity;

    public function __construct(IdentityInterface $identity)
    {
        $this->identity = $identity;
    }

    public function getIdentity(): IdentityInterface
    {
        return $this->identity;
    }
}
