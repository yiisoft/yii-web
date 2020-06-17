<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Provider;

use Yiisoft\Di\Container;
use Yiisoft\Di\Support\ServiceProvider;
use Yiisoft\Yii\Web\Data\DataResponseFormatterInterface;
use Yiisoft\Yii\Web\Data\Formatter\HtmlDataResponseFormatter;

final class DataResponseFormatterProvider extends ServiceProvider
{
    /**
     * @suppress PhanAccessMethodProtected
     */
    public function register(Container $container): void
    {
        $container->set(DataResponseFormatterInterface::class, HtmlDataResponseFormatter::class);
    }
}
