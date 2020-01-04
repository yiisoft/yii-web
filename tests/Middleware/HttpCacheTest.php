<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Yii\Web\Middleware\HttpCache;

class HttpCacheTest extends TestCase
{
    /**
     * @test
     */
    public function validCacheResult(): void
    {
        $time = \time();
        $middleware = $this->createMiddlewareWithLastModified($time);
        $headers = [
            'If-Modified-Since' => $time,
        ];
        $response = $middleware->process($this->createServerRequest(Method::GET, $headers), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function invalidCacheResult(): void
    {
        $time = \time();
        $middleware = $this->createMiddlewareWithLastModified($time - 1);
        $headers = [
            'If-Modified-Since' => gmdate('D, d M Y H:i:s', $time) . 'GMT',
        ];
        $response = $middleware->process($this->createServerRequest(Method::GET, $headers), $this->createRequestHandler());
        $this->assertEquals(304, $response->getStatusCode());
    }

    private function createMiddlewareWithLastModified(int $lastModified)
    {
        $middleware = new HttpCache(new Psr17Factory());
        $middleware->setLastModified(static function (ServerRequestInterface $request, $params) use ($lastModified) {
            return $lastModified;
        });
        return $middleware;
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

    private function createServerRequest(string $method = Method::GET, $headers = [])
    {
        return new ServerRequest($method, '/', $headers);
    }
}
