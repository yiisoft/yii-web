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
use Yiisoft\Yii\Web\Middleware\FormatDataResponse;
use Yiisoft\Yii\Web\DataResponse;

class FormatDataResponseTest extends TestCase
{
    public function testFormatter(): void
    {
        $request = new ServerRequest('GET', '/test');
        $factory = new Psr17Factory();
        $dataResponse = new DataResponse(['test' => 'test'], 200, '', $factory);
        $route = Route::get('/test', static function () use ($dataResponse) {
            return $dataResponse;
        }, $this->getContainer())->addMiddleware(FormatDataResponse::class);
        $result = $route->process($request, $this->getRequestHandler());
        $result->getBody()->rewind();

        $this->assertSame('{"test":"test"}', $result->getBody()->getContents());
        $this->assertSame(['application/json'], $result->getHeader('Content-Type'));
    }

    private function getContainer(): ContainerInterface
    {
        return new class() implements ContainerInterface {
            private array $instances;

            public function __construct()
            {
                $this->instances = [
                    FormatDataResponse::class => new FormatDataResponse(new JsonResponseFormatter())
                ];
            }

            public function get($id)
            {
                return $this->instances[$id];
            }

            public function has($id)
            {
                return isset($this->instances[$id]);
            }
        };
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
