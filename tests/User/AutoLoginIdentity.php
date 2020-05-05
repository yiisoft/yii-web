<?php


namespace Yiisoft\Yii\Web\Tests\User;


use Yiisoft\Yii\Web\User\AutoLoginIdentityInterface;

final class AutoLoginIdentity implements AutoLoginIdentityInterface
{
    public const ID = '42';
    public const AUTH_KEY_CORRECT = 'auth-key-correct';
    public const AUTH_KEY_INCORRECT = 'auth-key-incorrect';

    public function getAuthKey(): string
    {
        return self::AUTH_KEY_CORRECT;
    }

    public function validateAuthKey(string $authKey): bool
    {
        return $authKey === $this->getAuthKey();
    }

    public function getId(): ?string
    {
        return self::ID;
    }
}
