<?php

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\Web\WebResponse;

class WebResponseTest extends TestCase
{
    public function testCreateResponse(): void
    {
        $factory = new Psr17Factory();
        $webResponse = new WebResponse('test', $factory, $factory);
        $webResponse->getBody()->rewind();

        $this->assertInstanceOf(ResponseInterface::class, $webResponse);
        $this->assertSame('test', $webResponse->getBody()->getContents());
    }
}
