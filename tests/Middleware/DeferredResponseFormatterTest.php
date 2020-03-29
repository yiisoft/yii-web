<?php

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\Route;
use Yiisoft\Serializer\JsonSerializer;
use Yiisoft\Yii\Web\Formatter\JsonResponseFormatter;
use Yiisoft\Yii\Web\Middleware\DeferredResponseFormatter;
use Yiisoft\Yii\Web\Response as WebResponse;

class DeferrredResponseFormatterTest extends TestCase
{
    public function testFormatter(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $request = new ServerRequest('GET', '/test');
        $streamFactory = new Psr17Factory();
        $response = new Response();
        $webResponse = new WebResponse(['test' => 'test'], $response, $streamFactory);
        $formatter = new JsonResponseFormatter(new JsonSerializer(), $streamFactory);
        $responseFormatter = new DeferredResponseFormatter($formatter);
        $route = Route::get('/test', function () use ($webResponse) {
            return $webResponse;
        }, $container)->addMiddleware($responseFormatter);
        $result = $route->process($request, $this->getRequestHandler());
        $result->getBody()->rewind();

        $this->assertSame('{"test":"test"}', $response->getBody()->getContents());
        $this->assertSame(['application/json'], $result->getHeader('Content-Type'));
    }

    private function getRequestHandler(): RequestHandlerInterface
    {
        return new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404);
            }
        };
    }
}
