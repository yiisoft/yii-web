<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Yii\Web\RateLimiter\CounterInterface;
use Yiisoft\Yii\Web\RateLimiter\RateLimiterMiddleware;

final class RateLimiterMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function singleRequestWorksAsExpected(): void
    {
        $counter = new FakeCounter(100, 100);
        $response = $this->createRateLimiter($counter)->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            [
                'X-Rate-Limit-Limit' => ['100'],
                'X-Rate-Limit-Remaining' => ['99'],
                'X-Rate-Limit-Reset' => ['100'],
            ],
            $response->getHeaders()
        );
    }

    /**
     * @test
     */
    public function limitingIsStartedWhenExpected(): void
    {
        $counter = new FakeCounter(2, 100);
        $middleware = $this->createRateLimiter($counter);

        // last allowed request
        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            [
                'X-Rate-Limit-Limit' => ['2'],
                'X-Rate-Limit-Remaining' => ['1'],
                'X-Rate-Limit-Reset' => ['100'],
            ],
            $response->getHeaders()
        );

        // first denied request
        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals(
            [
                'X-Rate-Limit-Limit' => ['2'],
                'X-Rate-Limit-Remaining' => ['0'],
                'X-Rate-Limit-Reset' => ['100'],
            ],
            $response->getHeaders()
        );
    }

    /**
     * @test
     */
    public function counterIdCouldBeSet(): void
    {
        $counter = new FakeCounter(100, 100);
        $middleware = $this->createRateLimiter($counter)->withCounterId('custom-id');
        $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals('custom-id', $counter->getId());
    }

    /**
     * @test
     */
    public function counterIdCouldBeSetWithCallback(): void
    {
        $counter = new FakeCounter(100, 100);
        $middleware = $this->createRateLimiter($counter)->withCounterIdCallback(
            static function (ServerRequestInterface $request) {
                return $request->getMethod();
            }
        );

        $middleware->process($this->createRequest(), $this->createRequestHandler());
        $this->assertEquals('GET', $counter->getId());
    }

    private function createRequestHandler(): RequestHandlerInterface
    {
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler
            ->method('handle')
            ->willReturn(new Response(200));

        return $requestHandler;
    }

    private function createRequest(string $method = Method::GET, string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }

    private function createRateLimiter(CounterInterface $counter): RateLimiterMiddleware
    {
        return new RateLimiterMiddleware($counter, new Psr17Factory());
    }
}
