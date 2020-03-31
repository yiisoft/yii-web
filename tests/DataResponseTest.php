<?php

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Yii\Web\Formatter\JsonResponseFormatter;
use Yiisoft\Yii\Web\DataResponse;

class DataResponseTest extends TestCase
{
    public function testCreateResponse(): void
    {
        $factory = new Psr17Factory();
        $dataResponse = new DataResponse('test', Status::OK, '', $factory);
        $dataResponse = $dataResponse->withHeader('Content-Type', 'application/json');
        $dataResponse->getBody()->rewind();

        $this->assertInstanceOf(ResponseInterface::class, $dataResponse);
        $this->assertInstanceOf(ResponseInterface::class, $dataResponse->getResponse());
        $this->assertSame(['application/json'], $dataResponse->getResponse()->getHeader('Content-Type'));
        $this->assertSame(['application/json'], $dataResponse->getHeader('Content-Type'));
        $this->assertSame($dataResponse->getResponse()->getBody(), $dataResponse->getBody());
        $this->assertSame('test', $dataResponse->getBody()->getContents());
    }

    public function testChangeResponseData(): void
    {
        $factory = new Psr17Factory();
        $dataResponse = new DataResponse('test', Status::OK, '', $factory);
        $data = $dataResponse->getData();
        $data .= '-changed';
        $dataResponse = $dataResponse->withData($data);
        $dataResponse->getBody()->rewind();

        $this->assertSame('test-changed', $dataResponse->getBody()->getContents());
    }

    public function testSetResponseFormatter(): void
    {
        $factory = new Psr17Factory();
        $dataResponse = new DataResponse('test', Status::OK, '', $factory);
        $dataResponse = $dataResponse->withResponseFormatter(new JsonResponseFormatter());

        $this->assertTrue($dataResponse->hasResponseFormatter());
    }
}
