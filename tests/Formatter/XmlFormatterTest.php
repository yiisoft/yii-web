<?php

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\Formatter\XmlResponseFormatter;
use Yiisoft\Yii\Web\WebResponse as WebResponse;

class XmlFormatterTest extends TestCase
{
    public function testFormatter(): void
    {
        $streamFactory = new Psr17Factory();
        $response = new Response();
        $webResponse = new WebResponse('test', $response, $streamFactory);
        $formatter = new XmlResponseFormatter();
        $result = $formatter->format($webResponse);
        $result->getBody()->rewind();

        $this->assertSame(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<response>test</response>\n",
            $response->getBody()->getContents()
        );
        $this->assertSame(['application/xml; UTF-8'], $result->getHeader('Content-Type'));
    }

    public function testFormatterEncoding(): void
    {
        $streamFactory = new Psr17Factory();
        $response = new Response();
        $webResponse = new WebResponse('test', $response, $streamFactory);
        $formatter = new XmlResponseFormatter($streamFactory);
        $formatter->setEncoding('ISO-8859-1');
        $result = $formatter->format($webResponse);
        $result->getBody()->rewind();

        $this->assertSame(
            "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<response>test</response>\n",
            $response->getBody()->getContents()
        );
        $this->assertSame(['application/xml; ISO-8859-1'], $result->getHeader('Content-Type'));
    }
}
