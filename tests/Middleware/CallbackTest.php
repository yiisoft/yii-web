<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Middleware\Callback;

final class CallbackTest extends TestCase
{
    /**
     * @test
     */
    public function processReturnHandleResponse()
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
    public function processReturnResponse400()
    {
        $middleware = new Callback(function () {
            return new Response(400);
        }, $this->createContainer());

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(400, $response->getStatusCode());
    }

    private function createContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            public function get($id)
            {
                // do nothing
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

    private function createRequest(): ServerRequestInterface
    {
        return new ServerRequest('GET', '/');
    }
}