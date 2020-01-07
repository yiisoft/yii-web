<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RateLimiter implements MiddlewareInterface
{
    private int $limit = 1000;

    private CounterInterface $counter;

    private ResponseFactoryInterface $responseFactory;

    private bool $autoincrement = true;

    public function __construct(CounterInterface $counter, ResponseFactoryInterface $responseFactory)
    {
        $this->counter = $counter;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->counter->init($request);

        if (!$this->isAllowed()) {
            return $this->createErrorResponse();
        }

        if ($this->autoincrement) {
            $this->counter->increment();
        }

        return $handler->handle($request);
    }

    public function withLimit(int $limit): self
    {
        $this->limit = $limit;

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

    private function isAllowed(): bool
    {
        return $this->counter->getCounterValue() < $this->limit;
    }
}
