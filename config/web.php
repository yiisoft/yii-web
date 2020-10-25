<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;
use Yiisoft\Yii\Web\ErrorHandler\ThrowableRendererInterface;

/**
 * @var array $params
 */

return [
    Aliases::class => [
        '__class' => Aliases::class,
        '__construct()' => [$params['aliases']],
    ],

    ThrowableRendererInterface::class => HtmlRenderer::class,

    HtmlRenderer::class => [
        '__class' => HtmlRenderer::class,
        '__construct()' => [
            $params['yiisoft/yii-web']['htmlRenderer']['templates'],
        ],
    ],
];
