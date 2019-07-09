<?php
return [
    'aliases' => [
        '@web' => '/',
    ],

    'app' => [
        '__class' => \Yiisoft\Yii\Web\Application::class,
    ],

    \Yiisoft\Yii\Web\ErrorHandler\ThrowableRendererInterface::class => \Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer::class,
];
