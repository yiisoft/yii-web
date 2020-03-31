<?php

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\Formatter\JsonResponseFormatter;
use Yiisoft\Yii\Web\WebResponse;

class JsonFormatterTest extends TestCase
{
    public function testFormatter(): void
    {
        $factory = new Psr17Factory();
        $webResponse = new WebResponse(['test' => 'test'], 200, $factory, $factory);
        $formatter = new JsonResponseFormatter();
        $result = $formatter->format($webResponse);
        $result->getBody()->rewind();

        $this->assertSame('{"test":"test"}', $result->getBody()->getContents());
        $this->assertSame(['application/json'], $result->getHeader('Content-Type'));
    }
}
