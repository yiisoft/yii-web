<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * RateLimiter helps to prevent abuse by limiting the number of requests that could be me made consequentially.
 *
 * For example, you may want to limit the API usage of each user to be at most 100 API calls within a period of 10 minutes.
 * If too many requests are received from a user within the stated period of the time, a response with status code 429
 * (meaning "Too Many Requests") should be returned.
 */
final class RateLimiterMiddleware implements MiddlewareInterface
{
    private CacheCounter $counter;

    private ResponseFactoryInterface $responseFactory;

    private string $counterId;

    /**
     * @var callable
     */
    private $counterIdCallback;

    public function __construct(CacheCounter $counter, ResponseFactoryInterface $responseFactory)
    {
        $this->counter = $counter;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->counter->setId($this->generateId($request));
        $result = $this->counter->incrementAndGetResult();

        if ($result->isLimitReached()) {
            $response = $this->createErrorResponse();
        } else {
            $response = $handler->handle($request);
        }

        return $this->addHeaders($response, $result);
    }

    public function withCounterIdCallback(?callable $callback): self
    {
        $new = clone $this;
        $new->counterIdCallback = $callback;

        return $new;
    }

    public function withCounterId(string $id): self
    {
        $new = clone $this;
        $new->counterId = $id;

        return $new;
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
        return strtolower($request->getMethod() . '-' . $request->getUri()->getPath());
    }

    public function addHeaders(ResponseInterface $response, RateLimitResult $result): ResponseInterface
    {
        return $response
            ->withHeader('X-Rate-Limit-Limit', $result->getLimit())
            ->withHeader('X-Rate-Limit-Remaining', $result->getRemaining())
            ->withHeader('X-Rate-Limit-Reset', $result->getReset());
    }
}
