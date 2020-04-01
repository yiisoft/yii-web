<?php

namespace Yiisoft\Yii\Web\Tests\Data\Formatter;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\Data\Formatter\XmlDataResponseFormatter;
use Yiisoft\Yii\Web\Data\DataResponse;

class XmlDataResponseFormatterTest extends TestCase
{
    public function testFormatter(): void
    {
        $factory = new Psr17Factory();
        $dataResponse = new DataResponse('test', $factory->createResponse(200, ''));
        $formatter = new XmlDataResponseFormatter();
        $result = $formatter->format($dataResponse);
        $result->getBody()->rewind();

        $this->assertSame(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<response>test</response>\n",
            $result->getBody()->getContents()
        );
        $this->assertSame(['application/xml; UTF-8'], $result->getHeader('Content-Type'));
    }

    public function testFormatterEncoding(): void
    {
        $factory = new Psr17Factory();
        $dataResponse = new DataResponse('test', $factory->createResponse(200, ''));
        $formatter = new XmlDataResponseFormatter();
        $formatter = $formatter->withEncoding('ISO-8859-1');
        $result = $formatter->format($dataResponse);
        $result->getBody()->rewind();

        $this->assertSame(
            "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<response>test</response>\n",
            $result->getBody()->getContents()
        );
        $this->assertSame(['application/xml; ISO-8859-1'], $result->getHeader('Content-Type'));
    }
}
