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
    public function testNotCacheableMethods(): void
    {
        $time = \time();
        $middleware = $this->createMiddlewareWithLastModified($time + 1);
        $response = $middleware->process($this->createServerRequest(Method::PATCH), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Last-Modified'));
    }

    public function testModifiedResultWithLastModified(): void
    {
        $time = \time();
        $middleware = $this->createMiddlewareWithLastModified($time + 1);
        $headers = [
            'If-Modified-Since' => gmdate('D, d M Y H:i:s', $time) . 'GMT',
        ];
        $response = $middleware->process($this->createServerRequest(Method::GET, $headers), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testModifiedResultWithEtag(): void
    {
        $etag = 'test-etag';
        $middleware = $this->createMiddlewareWithETag($etag);
        $headers = [
            'If-None-Match' => implode(',', [$etag]),
        ];
        $response = $middleware->process($this->createServerRequest(Method::GET, $headers), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($response->getHeaderLine('Etag'), $this->generateEtag($etag));
    }

    public function testNotModifiedResultWithLastModified(): void
    {
        $time = \time();
        $middleware = $this->createMiddlewareWithLastModified($time - 1);
        $headers = [
            'If-Modified-Since' => gmdate('D, d M Y H:i:s', $time) . 'GMT',
        ];
        $response = $middleware->process($this->createServerRequest(Method::GET, $headers), $this->createRequestHandler());
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
        $this->assertEquals(gmdate('D, d M Y H:i:s', $time - 1) . ' GMT', $response->getHeaderLine('Last-Modified'));
    }

    public function testNotModifiedResultWithEtag(): void
    {
        $etag = 'test-etag';
        $middleware = $this->createMiddlewareWithETag($etag);
        $headers = [
            'If-None-Match' => implode(',', [$this->generateEtag($etag)]),
        ];
        $response = $middleware->process($this->createServerRequest(Method::GET, $headers), $this->createRequestHandler());
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    private function createMiddlewareWithLastModified(int $lastModified): HttpCache
    {
        $middleware = new HttpCache(new Psr17Factory());
        $middleware->setLastModified(static function (ServerRequestInterface $request, $params) use ($lastModified) {
            return $lastModified;
        });
        return $middleware;
    }

    private function createMiddlewareWithETag(string $etag): HttpCache
    {
        $middleware = new HttpCache(new Psr17Factory());
        $middleware->setEtagSeed(static function (ServerRequestInterface $request, $params) use ($etag) {
            return $etag;
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

    private function createServerRequest(string $method = Method::GET, $headers = []): ServerRequestInterface
    {
        return new ServerRequest($method, '/', $headers);
    }

    private function generateEtag(string $seed, ?string $weakEtag = null): string
    {
        $etag = '"' . rtrim(base64_encode(sha1($seed, true)), '=') . '"';
        return $weakEtag ? 'W/' . $etag : $etag;
    }
}
