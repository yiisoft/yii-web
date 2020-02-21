<?php

namespace Yiisoft\Yii\Web\Tests\RequestHandler;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\RequestHandler\MiddlewareHandler;

final class MiddlewareHandlerTest extends TestCase
{
    public function testHandler(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                    return $handler->handle($request);
                }
            );
        $requestHandler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $handler = new MiddlewareHandler($middleware, $requestHandler);

        $result = $handler->handle($this->createMock(ServerRequestInterface::class));

        $this->assertSame($response, $result);
    }
}
