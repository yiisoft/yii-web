<?php

declare(strict_types=1);

/* @var array $params */

use Yiisoft\Yii\Web\Provider\DataResponseFactoryProvider;
use Yiisoft\Yii\Web\Provider\DataResponseFormatterProvider;
use Yiisoft\Yii\Web\Provider\HtmlRendererProvider;
use Yiisoft\Yii\Web\Provider\Psr17Provider;
use Yiisoft\Yii\Web\Provider\SessionProvider;
use Yiisoft\Yii\Web\Provider\ThrowableRendererProvider;

return [
    'yiisoft/yii-web/psr17' => Psr17Provider::class,
    'yiisoft/yii-web/dataresponsefactory' => DataResponseFactoryProvider::class,
    'yiisoft/yii-web/dataresponseformatter' => DataResponseFormatterProvider::class,
    'yiisoft/yii-web/htmlrenderer' => [
        '__class' => HtmlRendererProvider::class,
        '__construct()' => [
            $params['yiisoft/yii-web']['htmlRenderer']['templates']
        ],
    ],
    'yiisoft/yii-web/throwablerenderer' => ThrowableRendererProvider::class,
    'yiisoft/yii-web/session' => [
        '__class' => SessionProvider::class,
        '__construct()' => [
            $params['yiisoft/yii-web']['session']['options'],
            $params['yiisoft/yii-web']['session']['handler']
        ],
    ],
];
