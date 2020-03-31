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
use Yiisoft\Http\Status;
use Yiisoft\Router\Route;
use Yiisoft\Yii\Web\Formatter\JsonResponseFormatter;
use Yiisoft\Yii\Web\Middleware\WebResponseFormatter;
use Yiisoft\Yii\Web\WebResponse;

class WebResponseFormatterTest extends TestCase
{
    public function testFormatter(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $request = new ServerRequest('GET', '/test');
        $factory = new Psr17Factory();
        $webResponse = new WebResponse(['test' => 'test'], 200, $factory);
        $responseFormatter = new WebResponseFormatter(new JsonResponseFormatter());
        $route = Route::get('/test', static function () use ($webResponse) {
            return $webResponse;
        }, $container)->addMiddleware([$responseFormatter, 'process']);
        $result = $route->process($request, $this->getRequestHandler());
        $result->getBody()->rewind();

        $this->assertSame('{"test":"test"}', $result->getBody()->getContents());
        $this->assertSame(['application/json'], $result->getHeader('Content-Type'));
    }

    private function getRequestHandler(): RequestHandlerInterface
    {
        return new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(Status::NOT_FOUND);
            }
        };
    }
}
