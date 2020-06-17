<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests;

use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Yiisoft\Composer\Config\Builder;
use Yiisoft\Di\Container;

abstract class TestCase extends BaseTestCase
{
    protected ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createContainer();
    }

    protected function tearDowm(): void
    {
        parent::tearDown();

        unset($this->container);
    }

    private function createContainer(): void
    {
        $this->container = new Container(
            require Builder::path('web'),
            require Builder::path('providers-web')
        );
    }
}
