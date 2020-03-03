<?php

namespace Yiisoft\Yii\Web\User;

/**
 * AutoLoginMiddleware automatically logs user in based on "remember me" cookie
 */
class AutoLoginMiddleware
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
