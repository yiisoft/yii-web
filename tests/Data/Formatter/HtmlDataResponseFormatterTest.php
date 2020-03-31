<?php

namespace Yiisoft\Yii\Web\Tests\Data\Formatter;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\Data\Formatter\HtmlDataResponseFormatter;
use Yiisoft\Yii\Web\Data\DataResponse;

class HtmlDataResponseFormatterTest extends TestCase
{
    public function testFormatter(): void
    {
        $factory = new Psr17Factory();
        $dataResponse = new DataResponse('test', 200, '', $factory);
        $formatter = new HtmlDataResponseFormatter();
        $result = $formatter->format($dataResponse);
        $result->getBody()->rewind();

        $this->assertSame('test', $result->getBody()->getContents());
        $this->assertSame(['text/html; charset=UTF-8'], $result->getHeader('Content-Type'));
    }

    public function testFormatterEncoding(): void
    {
        $factory = new Psr17Factory();
        $dataResponse = new DataResponse('test', 200, '', $factory);
        $formatter = new HtmlDataResponseFormatter();
        $formatter = $formatter->withEncoding('ISO-8859-1');
        $result = $formatter->format($dataResponse);
        $result->getBody()->rewind();

        $this->assertSame('test', $result->getBody()->getContents());
        $this->assertSame(['text/html; charset=ISO-8859-1'], $result->getHeader('Content-Type'));
    }
}
