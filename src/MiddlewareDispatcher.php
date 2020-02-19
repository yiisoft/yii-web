<?php

namespace Yiisoft\Yii\Web;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Middleware\Callback;

/**
 * MiddlewareDispatcher
 */
final class MiddlewareDispatcher
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * @var RequestHandlerInterface|null
     */
    private $nextHandler;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Contains a chain of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     * @var RequestHandlerInterface|null stack of middleware
     */
    private ?RequestHandlerInterface $stack = null;

    public function __construct(
        ContainerInterface $container,
        RequestHandlerInterface $nextHandler = null
    ) {
        $this->container = $container;

        $responseFactory = $container->get(ResponseFactoryInterface::class);

        $this->nextHandler = $nextHandler ?? new NotFoundHandler($responseFactory);
    }

    private function addCallable(callable $callback): void
    {
        array_unshift($this->middlewares, new Callback($callback, $this->container));
    }

    public function addMiddleware($middleware): self
    {
        if (is_callable($middleware)) {
            $this->addCallable($middleware);
        } elseif ($middleware instanceof MiddlewareInterface) {
            array_unshift($this->middlewares, $middleware);
        } else {
            throw new \InvalidArgumentException('Middleware should be either callable or MiddlewareInterface instance. ' . get_class($middleware) . ' given.');
        }

        return $this;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process($request, $this->nextHandler);
    }

    private function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->stack === null) {
            for ($i = count($this->middlewares) - 1; $i >= 0; $i--) {
                $handler = $this->wrap($this->middlewares[$i], $handler);
            }
            $this->stack = $handler;
        }

        return $this->stack->handle($request);
    }

    /**
     * Wraps handler by middlewares
     */
    private function wrap(MiddlewareInterface $middleware, RequestHandlerInterface $handler): RequestHandlerInterface
    {
        return new class($middleware, $handler) implements RequestHandlerInterface {
            private MiddlewareInterface $middleware;
            private RequestHandlerInterface $handler;

            public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $handler)
            {
                $this->middleware = $middleware;
                $this->handler = $handler;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->middleware->process($request, $this->handler);
            }
        };
    }
}
