<?php
namespace Yiisoft\Yii\Web\User;

interface IdentityRepositoryInterface
{
    public function findIdentity(string $id): ?IdentityInterface;

    /**
     * Finds an identity by the given token.
     * @param string $token the token to be looked for
     * @param string $type the type of the token. The value of this parameter depends on the implementation and should
     * allow supporting multiple token types for a single identity.
     * @return IdentityInterface|null the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public function findIdentityByToken(string $token, string $type): ?IdentityInterface;
}
