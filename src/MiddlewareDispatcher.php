<?php

namespace Yiisoft\Web;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Web\Middleware\Callback;

/**
 * MiddlewareDispatcher
 */
class MiddlewareDispatcher implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $_middlewares;

    /**
     * @var RequestHandlerInterface
     */
    private $_fallbackHanlder;

    public function __construct(array $middlewares, ResponseFactoryInterface $responseFactory, RequestHandlerInterface $fallbackHandler = null)
    {
        if ($middlewares === []) {
            throw new \InvalidArgumentException('Middlewares should be defined.');
        }

        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                $middleware = new Callback($middleware);
            }
            $this->_middlewares[] = $middleware;
        }

        $this->_fallbackHanlder = $fallbackHandler ?? new NotFoundHandler($responseFactory);
    }

    public function add(MiddlewareInterface $middleware)
    {
        $this->_middlewares[] = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Last middleware in the queue has called on the request handler
        if (\count($this->_middlewares) === 0) {
            return $this->_fallbackHanlder->handle($request);
        }

        return array_shift($this->_middlewares)->process($request, $this);
    }
}
