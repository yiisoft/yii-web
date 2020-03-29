<?php

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\Formatter\HtmlResponseFormatter;
use Yiisoft\Yii\Web\Response as WebResponse;

class HtmlFormatterTest extends TestCase
{
    public function testFormatter(): void
    {
        $streamFactory = new Psr17Factory();
        $response = new Response();
        $webResponse = new WebResponse('test', $response, $streamFactory);
        $formatter = new HtmlResponseFormatter();
        $result = $formatter->format($webResponse);
        $result->getBody()->rewind();

        $this->assertSame('test', $response->getBody()->getContents());
        $this->assertSame(['text/html; charset=UTF-8'], $result->getHeader('Content-Type'));
    }
}
