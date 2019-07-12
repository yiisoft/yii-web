<?php
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;
use Yiisoft\Yii\Web\ErrorHandler\ThrowableRendererInterface;

return [
    Aliases::class => [
        '__class'   => Aliases::class,
        // @root needs to be redefined in the application config
        '@root'     => dirname(__DIR__),
        '@vendor'   => '@root/vendor',
        '@public'   => '@root/public',
        '@runtime'  => '@root/runtime',
        '@bower'    => '@vendor/bower-asset',
        '@npm'      => '@vendor/npm-asset',
        '@web' => '/',
    ],

    ThrowableRendererInterface::class => HtmlRenderer::class,
];
