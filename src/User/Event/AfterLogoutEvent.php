<?php
namespace Yiisoft\Yii\Web\User\Event;

use Yiisoft\Yii\Web\User\IdentityInterface;

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
