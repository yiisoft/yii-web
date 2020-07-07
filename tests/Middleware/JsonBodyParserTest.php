<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Middleware\JsonBodyParser;

final class JsonBodyParserTest extends TestCase
{
    public function testProcess()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $body
            ->expects($this->once())
            ->method('getContents')
            ->willReturn('{"test":"value"}');

        $request
            ->method('getHeaderLine')
            ->willReturn('application/json');

        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $request
            ->expects($this->once())
            ->method('withParsedBody')
            ->with(
                $this->equalTo(['test' => 'value'])
            )
            ->willReturnSelf();

        $parser = (new JsonBodyParser())->withAssoc(true);
        $parser->process($request, $this->createMock(RequestHandlerInterface::class));
    }

    public function testWithoutAssoc()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $object = new \stdClass();
        $object->test = 'value';

        $body
            ->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($object));

        $request
            ->method('getHeaderLine')
            ->willReturn('application/json');

        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $request
            ->expects($this->once())
            ->method('withParsedBody')
            ->with(
                $this->equalTo($object)
            )
            ->willReturnSelf();

        $parser = (new JsonBodyParser())->withAssoc(false);
        $parser->process($request, $this->createMock(RequestHandlerInterface::class));
    }

    public function testThrownException()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $body
            ->expects($this->once())
            ->method('getContents')
            ->willReturn('{"test": invalid json}');

        $request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->willReturn('application/json');

        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $this->expectException(\InvalidArgumentException::class);

        $parser = new JsonBodyParser();
        $parser->process($request, $this->createMock(RequestHandlerInterface::class));
    }

    public function testIgnoreInvalidUTF8()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $body
            ->expects($this->once())
            ->method('getContents')
            ->willReturn('{"test":"value","invalid":"' . chr(193) . '"}');

        $request
            ->method('getHeaderLine')
            ->willReturn('application/json');

        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $request
            ->expects($this->once())
            ->method('withParsedBody')
            ->with(
                $this->equalTo(['test' => 'value', 'invalid' => ''])
            )
            ->willReturnSelf();

        $parser = (new JsonBodyParser())->withAssoc(true);
        $parser->process($request, $this->createMock(RequestHandlerInterface::class));
    }

    public function testInvalidJson()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $body
            ->expects($this->once())
            ->method('getContents')
            ->willReturn('true');

        $request
            ->method('getHeaderLine')
            ->willReturn('application/json');

        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $request
            ->expects($this->once())
            ->method('withParsedBody')
            ->with(
                $this->equalTo(null)
            )
            ->willReturnSelf();

        $parser = (new JsonBodyParser())->withAssoc(true);
        $parser->process($request, $this->createMock(RequestHandlerInterface::class));
    }
}
