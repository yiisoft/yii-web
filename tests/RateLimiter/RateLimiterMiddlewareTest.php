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
use Yiisoft\Yii\Web\RateLimiter\Counter;
use Yiisoft\Yii\Web\RateLimiter\RateLimiterMiddleware;

final class RateLimiterMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function singleRequestWorksAsExpected(): void
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
    public function limitingIsStartedWhenExpected(): void
    {
        $middleware = $this->createRateLimiter($this->getCounter(10));

        for ($i = 0; $i < 8; $i++) {
            $middleware->process($this->createRequest(), $this->createRequestHandler());
        }

        // last allowed request
        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(
            [
                'X-Rate-Limit-Limit' => ['10'],
                'X-Rate-Limit-Remaining' => ['1'],
                'X-Rate-Limit-Reset' => ['3240000']
            ],
            $response->getHeaders()
        );

        // first denied request
        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertSame(
            [
                'X-Rate-Limit-Limit' => ['10'],
                'X-Rate-Limit-Remaining' => ['0'],
                'X-Rate-Limit-Reset' => ['3600000']
            ],
            $response->getHeaders()
        );
    }

    /**
     * @test
     */
    public function counterIdCouldBeSet(): void
    {
        $cache = new ArrayCache();
        $counter = new Counter(100, 3600, $cache);

        $middleware = $this->createRateLimiter($counter)->withCounterId('custom-id');
        $middleware->process($this->createRequest(), $this->createRequestHandler());

        $this->assertTrue($cache->has($counter->getCacheKey()));
    }

    /**
     * @test
     */
    public function counterIdCouldBeSetWithCallback(): void
    {
        $cache = new ArrayCache();
        $counter = new Counter(100, 3600, $cache);

        $middleware = $this->createRateLimiter($counter)->withCounterIdCallback(
            static function (ServerRequestInterface $request) {
                return $request->getMethod();
            }
        );

        $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertTrue($cache->has($counter->getCacheKey()));
    }

    private function getCounter(int $limit): Counter
    {
        return new Counter($limit, 3600, new ArrayCache());
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

    private function createRateLimiter(Counter $counter): RateLimiterMiddleware
    {
        return new RateLimiterMiddleware($counter, new Psr17Factory());
    }
}
