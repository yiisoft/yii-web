<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Yii\Web\Middleware\Callback;

final class CallbackTest extends TestCase
{
    public function testHandlerIsPassedToCallback(): void
    {
        $middleware = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        }, $this->createContainer());

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCallbackResultReturned(): void
    {
        $middleware = new Callback(function () {
            return new Response(400);
        }, $this->createContainer());

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testRequestIsPassedToCallback(): void
    {
        $requestMethod = Method::PUT;
        $requestUri = '/test/request/uri';
        $middleware = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($requestMethod, $requestUri) {
            $this->assertEquals($request->getMethod(), $requestMethod);
            $this->assertEquals($request->getUri(), $requestUri);
            return $handler->handle($request);
        }, $this->createContainer());

        $middleware->process($this->createRequest($requestMethod, $requestUri), $this->createRequestHandler());
    }

    public function testCheckDiContainerCalled(): void
    {
        $middleware = new Callback(function (Response $response) {
            return $response;
        }, $this->createContainer());

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(404, $response->getStatusCode());
    }

    private function createContainer(): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturn(new Response(404));

        return $container;
    }

    private function createRequestHandler(): RequestHandlerInterface
    {
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler
            ->method('handle')
            ->willReturn(new Response(200));

        return $requestHandler;
    }

    private function createRequest(string $method = Method::GET, string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }
}
