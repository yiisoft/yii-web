<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Provider;

use Yiisoft\Di\Container;
use Yiisoft\Di\Support\ServiceProvider;
use Yiisoft\Yii\Web\Data\DataResponseFactory;
use Yiisoft\Yii\Web\Data\DataResponseFactoryInterface;

final class DataResponseFactoryProvider extends ServiceProvider
{
    /**
     * @suppress PhanAccessMethodProtected
     */
    public function register(Container $container): void
    {
        $container->set(DataResponseFactoryInterface::class, DataResponseFactory::class);
    }
}
