<?php

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\Web\Formatter\JsonResponseFormatter;
use Yiisoft\Yii\Web\WebResponse;

class WebResponseTest extends TestCase
{
    public function testCreateResponse(): void
    {
        $factory = new Psr17Factory();
        $webResponse = new WebResponse('test', 200, $factory);
        $webResponse = $webResponse->withHeader('Content-Type', 'application/json');
        $webResponse->getBody()->rewind();

        $this->assertInstanceOf(ResponseInterface::class, $webResponse);
        $this->assertInstanceOf(ResponseInterface::class, $webResponse->getResponse());
        $this->assertSame(['application/json'], $webResponse->getResponse()->getHeader('Content-Type'));
        $this->assertSame(['application/json'], $webResponse->getHeader('Content-Type'));
        $this->assertSame($webResponse->getResponse()->getBody(), $webResponse->getBody());
        $this->assertSame('test', $webResponse->getResponse()->getBody()->getContents());
        $webResponse->getBody()->rewind();
        $this->assertSame('test', $webResponse->getBody()->getContents());
    }

    public function testChangeResponseData(): void
    {
        $factory = new Psr17Factory();
        $webResponse = new WebResponse('test', 200, $factory);
        $data = $webResponse->getData();
        $data .= '-changed';
        $webResponse = $webResponse->withData($data);
        $webResponse->getBody()->rewind();

        $this->assertSame('test-changed', $webResponse->getBody()->getContents());
    }

    public function testSetResponseFormatter(): void
    {
        $factory = new Psr17Factory();
        $webResponse = new WebResponse('test', 200, $factory);
        $webResponse = $webResponse->withResponseFormatter(new JsonResponseFormatter());

        $this->assertTrue($webResponse->hasResponseFormatter());
    }
}
