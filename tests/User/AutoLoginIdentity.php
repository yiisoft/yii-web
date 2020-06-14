<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\User;

use Yiisoft\Yii\Web\User\AutoLoginIdentityInterface;

final class AutoLoginIdentity implements AutoLoginIdentityInterface
{
    public const ID = '42';
    public const KEY_CORRECT = 'auto-login-key-correct';
    public const KEY_INCORRECT = 'auto-login-key-incorrect';

    public function getAutoLoginKey(): string
    {
        return self::KEY_CORRECT;
    }

    public function validateAutoLoginKey(string $key): bool
    {
        return $key === $this->getAutoLoginKey();
    }

    public function getId(): ?string
    {
        return self::ID;
    }
}
