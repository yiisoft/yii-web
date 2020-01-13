<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Http\Method;
use Yiisoft\Yii\Web\RateLimiter\CacheCounter;
use Yiisoft\Yii\Web\RateLimiter\RateLimiterMiddleware;

final class RateLimiterTest extends TestCase
{
    /**
     * @test
     */
    public function singleRequestIsAllowed(): void
    {
        $middleware = $this->createRateLimiter($this->getCounter(1000));
        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(
            [
                'X-Rate-Limit-Limit' => ['1000'],
                'X-Rate-Limit-Remaining' => ['999'],
                'X-Rate-Limit-Reset' => ['3600']
            ],
            $response->getHeaders()
        );
    }

    /**
     * @test
     */
    public function moreThanDefaultNumberOfRequestsIsNotAllowed(): void
    {
        $middleware = $this->createRateLimiter($this->getCounter(100));

        for ($i = 0; $i < 99; $i++) {
            $middleware->process($this->createRequest(), $this->createRequestHandler());
        }

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertSame(
            [
                'X-Rate-Limit-Limit' => ['100'],
                'X-Rate-Limit-Remaining' => ['0'],
                'X-Rate-Limit-Reset' => ['3600000']
            ],
            $response->getHeaders()
        );
    }

    /**
     * @test
     */
    public function customLimitWorksAsExpected(): void
    {
        $middleware = $this->createRateLimiter($this->getCounter(10));

        for ($i = 0; $i < 8; $i++) {
            $middleware->process($this->createRequest(), $this->createRequestHandler());
        }

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function withManualCounterId(): void
    {
        $cache = new ArrayCache();
        $counter = new CacheCounter(100, 3600, $cache);

        $middleware = $this->createRateLimiter($counter)->withCounterId('custom-id');
        $middleware->process($this->createRequest(), $this->createRequestHandler());

        $this->assertTrue($cache->has(CacheCounter::ID_PREFIX . 'custom-id'));
    }

    /**
     * @test
     */
    public function withManualCounterByCallback(): void
    {
        $cache = new ArrayCache();
        $counter = new CacheCounter(100, 3600, $cache);

        $middleware = $this->createRateLimiter($counter)->withCounterIdCallback(
            static function (ServerRequestInterface $request) {
                return $request->getMethod();
            }
        );

        $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertTrue($cache->has(CacheCounter::ID_PREFIX . 'GET'));
    }

    private function getCounter(int $limit): CacheCounter
    {
        return new CacheCounter($limit, 3600, new ArrayCache());
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

    private function createRateLimiter(CacheCounter $counter): RateLimiterMiddleware
    {
        return new RateLimiterMiddleware($counter, new Psr17Factory());
    }
}
