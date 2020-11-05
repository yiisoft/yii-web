<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Auth\AuthenticationMethodInterface;
use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;
use Yiisoft\Yii\Web\ErrorHandler\ThrowableRendererInterface;
use Yiisoft\Yii\Web\User\UserAuth;

/**
 * @var array $params
 */

return [
    Aliases::class => [
        '__class' => Aliases::class,
        '__construct()' => [$params['yiisoft/aliases']['aliases']],
    ],

    HtmlRenderer::class => [
        '__class' => HtmlRenderer::class,
        '__construct()' => [
            $params['yiisoft/yii-web']['htmlRenderer']['templates'],
        ],
    ],

    ThrowableRendererInterface::class => HtmlRenderer::class,

    UserAuth::class => [
        '__class' => UserAuth::class,
        'withAuthUrl()' => [$params['yiisoft/yii-web']['userAuth']['authUrl']]
    ],

    AuthenticationMethodInterface::class => UserAuth::class
];
