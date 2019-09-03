<?php
namespace Yiisoft\Yii\Web\User\Event;

use Yiisoft\Yii\Web\User\IdentityInterface;

class BeforeLoginEvent
{
    private $identity;
    private $duration;
    private $isValid = true;

    public function __construct(IdentityInterface $identity, int $duration)
    {
        $this->identity = $identity;
        $this->duration = $duration;
    }

    public function invalidate(): void
    {
        $this->isValid = false;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @return IdentityInterface
     */
    public function getIdentity(): IdentityInterface
    {
        return $this->identity;
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }
}
