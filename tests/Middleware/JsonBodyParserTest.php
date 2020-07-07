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
        $parser = (new JsonBodyParser());
        $parser->process(
            $this->createMockRequest('{"test":"value"}', ['test' => 'value']),
            $this->createMock(RequestHandlerInterface::class)
        );
    }

    public function testWithoutAssoc()
    {
        $object = new \stdClass();
        $object->test = 'value';

        $parser = (new JsonBodyParser())->withoutAssoc();
        $parser->process(
            $this->createMockRequest(json_encode($object), $object),
            $this->createMock(RequestHandlerInterface::class)
        );
    }

    public function testThrownException()
    {
        $this->expectException(\JsonException::class);

        $parser = new JsonBodyParser();
        $parser->process(
            $this->createMockRequest('{"test": invalid json}'),
            $this->createMock(RequestHandlerInterface::class)
        );
    }

    public function testIgnoreInvalidUTF8()
    {
        $parser = (new JsonBodyParser());
        $parser->process(
            $this->createMockRequest(
                '{"test":"value","invalid":"' . chr(193) . '"}',
                ['test' => 'value', 'invalid' => '']
            ),
            $this->createMock(RequestHandlerInterface::class)
        );
    }

    public function testInvalidJson()
    {
        $parser = (new JsonBodyParser());
        $parser->process($this->createMockRequest('true', null), $this->createMock(RequestHandlerInterface::class));
    }

    private function createMockRequest(string $rawBody, $expect = null): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $body
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($rawBody);

        $request
            ->method('getHeaderLine')
            ->willReturn('application/json');

        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $args = func_get_args();

        if (count($args) === 2) {
            $request
                ->expects($this->once())
                ->method('withParsedBody')
                ->with(
                    $this->equalTo($expect)
                )
                ->willReturnSelf();
        }

        return $request;
    }
}
