<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Provider;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\Support\ServiceProvider;
use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;
use Yiisoft\Yii\Web\ErrorHandler\ThrowableRendererInterface;

final class ThrowableRendererProvider extends ServiceProvider
{
    private array $templates;

    public function __construct(array $templates = [])
    {
        $this->templates = $templates;
    }

    /**
     * @suppress PhanAccessMethodProtected
     */
    public function register(Container $container): void
    {
        $container->set(ThrowableRendererInterface::class, HtmlRenderer::class);
    }
}
