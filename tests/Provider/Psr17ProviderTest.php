<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Provider;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Yiisoft\Yii\Web\Tests\TestCase;

final class Psr17ProviderTest extends TestCase
{
    public function testProviderConfig(): void
    {
        $this->assertNotEmpty($this->container->get(RequestFactoryInterface::class));
        $this->assertNotEmpty($this->container->get(ResponseFactoryInterface::class));
        $this->assertNotEmpty($this->container->get(ServerRequestFactoryInterface::class));
        $this->assertNotEmpty($this->container->get(StreamFactoryInterface::class));
        $this->assertNotEmpty($this->container->get(UploadedFileFactoryInterface::class));
        $this->assertNotEmpty($this->container->get(UriFactoryInterface::class));
    }
}
