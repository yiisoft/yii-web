<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;

final class RateLimiter implements MiddlewareInterface
{
    private int $limit = 1000;

    private int $interval = 360;

    private ?string $cacheKey = null;

    /**
     * @var callable
     */
    private $cacheKeyCallback;

    private CacheInterface $cache;

    private ResponseFactoryInterface $responseFactory;

    private bool $autoincrement = true;

    public function __construct(CacheInterface $cache, ResponseFactoryInterface $responseFactory)
    {
        $this->cache = $cache;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->setupCacheParams($request);

        if (!$this->isAllow()) {
            return $this->createErrorResponse();
        }

        if ($this->autoincrement) {
            $this->increment();
        }

        return $handler->handle($request);
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param int $interval in seconds
     * @return $this
     */
    public function setInterval(int $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    public function setCacheKey(string $key): self
    {
        $this->cacheKey = $key;

        return $this;
    }

    public function setCacheKeyCallback(callable $callback): self
    {
        $this->cacheKeyCallback = $callback;

        return $this;
    }

    public function setAutoIncrement(bool $increment): self
    {
        $this->autoincrement = $increment;

        return $this;
    }

    private function createErrorResponse(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(429);
        $response->getBody()->write('Too Many Requests');

        return $response;
    }

    private function isAllow(): bool
    {
        return $this->getCounterValue() < $this->limit;
    }

    private function increment(): void
    {
        $value = $this->getCounterValue();
        $value++;

        $this->setCounterValue($value);
    }

    private function setupCacheParams(ServerRequestInterface $request): void
    {
        $this->cacheKey = $this->setupCacheKey($request);
    }

    private function setupCacheKey(ServerRequestInterface $request): string
    {
        if ($this->cacheKeyCallback !== null) {
            return \call_user_func($this->cacheKeyCallback, $request);
        }

        return $this->cacheKey ?? $this->generateCacheKey($request);
    }

    private function generateCacheKey(ServerRequestInterface $request): string
    {
        return strtolower('rate-limiter-' . $request->getMethod() . '-' . $request->getUri()->getPath());
    }

    private function getCounterValue(): int
    {
        return $this->cache->get($this->cacheKey, 0);
    }

    private function setCounterValue(int $value): void
    {
        $this->cache->set($this->cacheKey, $value, $this->interval);
    }
}
