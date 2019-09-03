<?php
namespace Yiisoft\Yii\Web\User\Event;

use Yiisoft\Yii\Web\User\IdentityInterface;

class AfterLogoutEvent
{
    private $identity;

    /**
     * AfterLogoutEvent constructor.
     * @param IdentityInterface $identity
     */
    public function __construct(IdentityInterface $identity)
    {
        $this->identity = $identity;
    }

    /**
     * @return IdentityInterface
     */
    public function getIdentity(): IdentityInterface
    {
        return $this->identity;
    }
}
