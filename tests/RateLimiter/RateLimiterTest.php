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
use Yiisoft\Yii\Web\RateLimiter\CounterQuota;
use Yiisoft\Yii\Web\RateLimiter\GenericCellRateAlgorithm;
use Yiisoft\Yii\Web\RateLimiter\RateLimiter;

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
    }

    /**
     * @test
     */
    public function moreThanDefaultNumberOfRequestsIsNotAllowed(): void
    {
        $middleware = $this->createRateLimiter($this->getCounter(1000));

        for ($i = 0; $i < 999; $i++) {
            $middleware->process($this->createRequest(), $this->createRequestHandler());
        }

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(429, $response->getStatusCode());
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

    private function getCounter(int $limit): CacheCounter
    {
        return new CacheCounter(new CounterQuota($limit, 3600), new ArrayCache(), new GenericCellRateAlgorithm());
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

    private function createRateLimiter(CacheCounter $counter): RateLimiter
    {
        return new RateLimiter($counter, new Psr17Factory());
    }
}
