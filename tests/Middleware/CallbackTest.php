<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Middleware\Callback;

final class CallbackTest extends TestCase
{
    public function testProcess(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $requestPassed = false;
        $handlerPassed = false;

        $callback = function ($r, $h) use ($request, $handler, &$requestPassed, &$handlerPassed) {
            $handlerPassed = $request === $r;
            $requestPassed = $handler === $h;

            return $this->createMock(ResponseInterface::class);
        };
        $middleware = new Callback($callback, $container);

        $this->assertFalse($requestPassed);
        $this->assertFalse($handlerPassed);

        $middleware->process($request, $handler);

        $this->assertTrue($requestPassed);
        $this->assertTrue($handlerPassed);
    }

    public function testProcessResult(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $callback = static function () use ($response) {
            return $response;
        };
        $middleware = new Callback($callback, $container);

        $result = $middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }
}
