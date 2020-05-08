<?php

use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;
use Yiisoft\Yii\Web\ErrorHandler\ThrowableRendererInterface;

/**
 * @var array $params
 */

return [
    Aliases::class => [
        '__class'   => Aliases::class,
        '__construct()' => [$params['aliases']],
    ],

    ThrowableRendererInterface::class => HtmlRenderer::class,
    HtmlRenderer::class => [
        '__class' => HtmlRenderer::class,
        '__construct()' => [
            $params['HtmlRenderer']['templates'] ?? [],
        ],
    ]
];
