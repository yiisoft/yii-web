<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * RateLimiter limits the number of consequential requests ({@see CacheCounter::$limit}) that could be processed per
 * {@see CacheCounter::$interval}. If the number is exceeded, middleware responds with HTTP code 429, "Too Many Requests"
 * until limit expires.
 */
final class RateLimiter implements MiddlewareInterface
{
    private CounterInterface $counter;

    private ResponseFactoryInterface $responseFactory;

    private string $counterId;

    /**
     * @var callable
     */
    private $counterIdCallback;

    public function __construct(CounterInterface $counter, ResponseFactoryInterface $responseFactory)
    {
        $this->counter = $counter;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->counter->setId($this->generateId($request));

        if ($this->counter->limitIsReached()) {
            return $this->createErrorResponse();
        }

        return $handler->handle($request);
    }

    public function withCounterIdCallback(callable $callback): self
    {
        $this->counterIdCallback = $callback;

        return $this;
    }

    public function withCounterId(string $id): self
    {
        $this->counterId = $id;

        return $this;
    }

    private function createErrorResponse(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(429);
        $response->getBody()->write('Too Many Requests');

        return $response;
    }

    private function generateId(ServerRequestInterface $request): string
    {
        if ($this->counterIdCallback !== null) {
            return \call_user_func($this->counterIdCallback, $request);
        }

        return $this->counterId ?? $this->generateIdFromRequest($request);
    }

    private function generateIdFromRequest(ServerRequestInterface $request): string
    {
        return strtolower('rate-limiter-' . $request->getMethod() . '-' . $request->getUri()->getPath());
    }
}
