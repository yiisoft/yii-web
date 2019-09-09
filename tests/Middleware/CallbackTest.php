<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\Method;
use Yiisoft\Yii\Web\Middleware\Callback;

final class CallbackTest extends TestCase
{
    /**
     * @test
     */
    public function handlerIsPassedToCallback()
    {
        $middleware = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        }, $this->createContainer());

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function callbackResultReturned()
    {
        $middleware = new Callback(function () {
            return new Response(400);
        }, $this->createContainer());

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function requestIsPassedToCallback()
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

    /**
     * @test
     */
    public function checkDiContainerCalled()
    {
        $middleware = new Callback(function (Response $response) {
            return $response;
        }, $this->createContainer());

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(404, $response->getStatusCode());
    }

    private function createContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {

            public function get($id)
            {
                return new Response(404);
            }

            public function has($id)
            {
                // do nothing
            }
        };
    }

    private function createRequestHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };
    }

    private function createRequest(string $method = Method::GET, string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }
}
