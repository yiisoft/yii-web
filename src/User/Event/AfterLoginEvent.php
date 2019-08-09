<?php
namespace Yiisoft\Yii\Web\User\Event;

use Yiisoft\Yii\Web\User\IdentityInterface;

class AfterLoginEvent
{
    private $identity;
    private $duration;

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
