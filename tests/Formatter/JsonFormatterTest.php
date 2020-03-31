<?php

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\Formatter\JsonResponseFormatter;
use Yiisoft\Yii\Web\WebResponse as WebResponse;

class JsonFormatterTest extends TestCase
{
    public function testFormatter(): void
    {
        $streamFactory = new Psr17Factory();
        $response = new Response();
        $webResponse = new WebResponse(['test' => 'test'], $response, $streamFactory);
        $formatter = new JsonResponseFormatter();
        $result = $formatter->format($webResponse);
        $result->getBody()->rewind();

        $this->assertSame('{"test":"test"}', $response->getBody()->getContents());
        $this->assertSame(['application/json'], $result->getHeader('Content-Type'));
    }
}
