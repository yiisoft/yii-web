<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Yii\Web\RateLimiter\RateLimiter;
use Yiisoft\Yii\Web\RateLimiter\CounterInterface;

final class RateLimiterTest extends TestCase
{
    /**
     * @test
     */
    public function singleRequestIsAllowed(): void
    {
        $middleware = $this->createRateLimiter($this->getCounter());
        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function moreThanDefaultNumberOfRequestsIsNotAllowed(): void
    {
        $middleware = $this->createRateLimiter($this->getCounter());

        for ($i = 0; $i < 1000; $i++) {
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
        $middleware = $this->createRateLimiter($this->getCounter())->withLimit(11);

        for ($i = 0; $i < 10; $i++) {
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
    public function disableAutoIncrement(): void
    {
        $counter = $this->getCounter();
        $middleware = $this->createRateLimiter($counter)->setAutoIncrement(false);
        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(0, $counter->getCounterValue());
    }

    private function getCounter(): CounterInterface
    {
        return new class implements CounterInterface {
            private int $count = 0;

            public function init(ServerRequestInterface $request): CounterInterface
            {
                return $this;
            }

            public function setIdCallback(callable $callback): CounterInterface
            {
                return $this;
            }

            public function setId(string $id): CounterInterface
            {
                return $this;
            }

            public function setInterval(int $interval): CounterInterface
            {
                return $this;
            }

            public function increment(): void
            {
                $this->count++;
            }

            public function getCounterValue(): int
            {
                return $this->count;
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

    private function createRequest(string $method = Method::GET, string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }

    private function createRateLimiter(CounterInterface $counter): RateLimiter
    {
        return new RateLimiter($counter, new Psr17Factory());
    }
}
