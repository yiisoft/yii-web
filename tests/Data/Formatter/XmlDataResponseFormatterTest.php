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
        $dataResponse = new DataResponse('test', 200, '', $factory);
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
        $dataResponse = new DataResponse('test', 200, '', $factory);
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

    public function testFormatterVersion(): void
    {
        $factory = new Psr17Factory();
        $dataResponse = new DataResponse('test', 200, '', $factory);
        $formatter = new XmlDataResponseFormatter();
        $formatter = $formatter->withVersion('1.1');
        $result = $formatter->format($dataResponse);
        $result->getBody()->rewind();

        $this->assertSame(
            "<?xml version=\"1.1\" encoding=\"UTF-8\"?>\n<response>test</response>\n",
            $result->getBody()->getContents()
        );
        $this->assertSame(['application/xml; UTF-8'], $result->getHeader('Content-Type'));
    }

    public function testFormatterRootTag(): void
    {
        $factory = new Psr17Factory();
        $dataResponse = new DataResponse('test', 200, '', $factory);
        $formatter = new XmlDataResponseFormatter();
        $formatter = $formatter->withRootTag('exampleRootTag');
        $result = $formatter->format($dataResponse);
        $result->getBody()->rewind();

        $this->assertSame(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<exampleRootTag>test</exampleRootTag>\n",
            $result->getBody()->getContents()
        );
        $this->assertSame(['application/xml; UTF-8'], $result->getHeader('Content-Type'));
    }

    public function testFormatterItemTagWhenNameIsEmptyOrInvalid(): void
    {
        $factory = new Psr17Factory();
        $data = [
            'test',
            'validName' => 'test',
            '1_invalidName' => 'test'
        ];
        $dataResponse = new DataResponse($data, 200, '', $factory);
        $formatter = new XmlDataResponseFormatter();
        $formatter = $formatter->withItemTag('customItemTag');
        $result = $formatter->format($dataResponse);
        $result->getBody()->rewind();

        $this->assertSame(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<response><customItemTag>test</customItemTag><validName>test</validName><customItemTag>test</customItemTag></response>\n",
            $result->getBody()->getContents()
        );
        $this->assertSame(['application/xml; UTF-8'], $result->getHeader('Content-Type'));
    }
}
