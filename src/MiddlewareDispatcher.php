<?php

namespace yii\web;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * MiddlewareDispatcher.
 */
class MiddlewareDispatcher implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;

    /**
     * @var RequestHandlerInterface
     */
    private $fallbackHandler;

    public function __construct(array $middlewares, ResponseFactoryInterface $responseFactory, RequestHandlerInterface $fallbackHandler = null)
    {
        $this->middlewares = $middlewares;
        $this->fallbackHandler = $fallbackHandler ?? new NotFoundHandler($responseFactory);
    }

    public function add(MiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Last middleware in the queue has called on the request handler.
        if (0 === \count($this->middlewares)) {
            return $this->fallbackHandler->handle($request);
        }

        return array_shift($this->middlewares)->process($request, $this);
    }
}
