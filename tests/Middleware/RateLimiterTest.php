<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Http\Method;
use Yiisoft\Yii\Web\Middleware\RateLimiter;

final class RateLimiterTest extends TestCase
{
    /**
     * @test
     */
    public function singleRequestIsAllowed(): void
    {
        $middleware = $this->createRateLimiter($this->getCache());
        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function moreThanDefaultNumberOfRequestsIsNotAllowed(): void
    {
        $cache = $this->getCache();
        $this->setRateLimiterCurrentRequestNumber($cache, 1000);

        $middleware = $this->createRateLimiter($cache);
        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function customLimitWorksAsExpected(): void
    {
        $cache = $this->getCache();
        $this->setRateLimiterCurrentRequestNumber($cache, 10);

        $middleware = $this->createRateLimiter($cache)->setLimit(11);

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function customCacheKey(): void
    {
        $cache = $this->getCache();
        $cache->set('custom-cache-key', 999);

        $middleware = $this->createRateLimiter($cache)->setCacheKey('custom-cache-key');

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function customCacheKeyCallback(): void
    {
        $cache = $this->getCache();
        $cache->set('POST', 1000);

        $middleware = $this->createRateLimiter($cache)
            ->setCacheKeyByCallback(
                static function (ServerRequestInterface $request) {
                    return $request->getMethod();
                }
            );

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());

        $response = $middleware->process($this->createRequest(Method::POST), $this->createRequestHandler());
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function customCacheTtl(): void
    {
        $middleware = $this->createRateLimiter($this->getCache())
            ->setLimit(1)
            ->setCacheTtl(1);

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(429, $response->getStatusCode());

        sleep(1);

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function disableAutoIncrement(): void
    {
        $cache = $this->getCache();

        $middleware = $this->createRateLimiter($cache)->setAutoIncrement(false);
        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(0, $cache->get('rate-limiter-get-/'));
    }

    private function setRateLimiterCurrentRequestNumber(CacheInterface $cache, int $number, $method = 'get', $path = '/'): void
    {
        $cache->set("rate-limiter-$method-$path", 1000);
    }

    private function getCache(): CacheInterface
    {
        return new ArrayCache();
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

    private function createRateLimiter(CacheInterface $cache): RateLimiter
    {
        return new RateLimiter($cache, new Psr17Factory());
    }
}
