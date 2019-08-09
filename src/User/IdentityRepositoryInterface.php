<?php
namespace Yiisoft\Yii\Web\User;

interface IdentityRepositoryInterface
{
    public function findIdentity($id): self;

    /**
     * Finds an identity by the given token.
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation and should
     * allow supporting multiple token types for a single identity.
     * @return IdentityInterface|null the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public function findIdentityByToken(string $token, string $type): ?self;
}
