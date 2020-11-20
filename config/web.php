<?php

declare(strict_types=1);

use Yiisoft\Auth\AuthenticationMethodInterface;
use Yiisoft\Yii\Web\User\UserAuth;

/**
 * @var array $params
 */

return [
    UserAuth::class => [
        '__class' => UserAuth::class,
        'withAuthUrl()' => [$params['yiisoft/yii-web']['userAuth']['authUrl']],
    ],

    AuthenticationMethodInterface::class => UserAuth::class,
];
