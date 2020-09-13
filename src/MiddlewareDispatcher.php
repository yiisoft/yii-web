<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Web\Event\AfterMiddleware;
use Yiisoft\Yii\Web\Event\BeforeMiddleware;

/**
 * MiddlewareDispatcher
 */
final class MiddlewareDispatcher
{
    /**
     * @var \SplStack
     */
    private \SplStack $middlewares;

    private RequestHandlerInterface $nextHandler;
    private ContainerInterface $container;

    public function __construct(
        ContainerInterface $container,
        RequestHandlerInterface $nextHandler = null
    ) {
        $this->middlewares = new \SplStack();
        $this->container = $container;
        $this->nextHandler = $nextHandler ?? new NotFoundHandler($container->get(ResponseFactoryInterface::class));
    }

    /**
     * @param callable|MiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware($middleware): self
    {
        if ($middleware instanceof MiddlewareInterface) {
            $this->middlewares->push($middleware);
            return $this;
        }

        if (is_callable($middleware)) {
            $this->middlewares->push($this->getCallbackMiddleware($middleware, $this->container));
            return $this;
        }

        throw new \InvalidArgumentException(
            'Middleware should be either callable or MiddlewareInterface instance. ' . get_class(
                $middleware
            ) . ' given.'
        );
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process($request, $this->nextHandler);
    }

    private function process(ServerRequestInterface $request, RequestHandlerInterface $nextHandler): ResponseInterface
    {
        $dispatcher = $this->container->get(EventDispatcherInterface::class);

        return $this->createMiddlewareStackHandler($nextHandler, $dispatcher)->handle($request);
    }

    private function createMiddlewareStackHandler(
        RequestHandlerInterface $nextHandler,
        EventDispatcherInterface $dispatcher
    ): RequestHandlerInterface {
        return new class($this->middlewares, $nextHandler, $dispatcher) implements RequestHandlerInterface {
            private ?\SplStack $stack;
            private RequestHandlerInterface $fallbackHandler;
            private EventDispatcherInterface $eventDispatcher;

            public function __construct(\SplStack $stack, RequestHandlerInterface $fallbackHandler, EventDispatcherInterface $eventDispatcher)
            {
                $this->stack = clone $stack;
                $this->fallbackHandler = $fallbackHandler;
                $this->eventDispatcher = $eventDispatcher;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($this->stack === null) {
                    throw \RuntimeException('Middleware handler was called already');
                }

                if ($this->stack->isEmpty()) {
                    $this->stack = null;
                    return $this->fallbackHandler->handle($request);
                }

                /** @var MiddlewareInterface $middleware */
                $middleware = $this->stack->pop();
                $next = clone $this; // deep clone is not used intentionally
                $this->stack = null; // mark queue as processed at this nesting level

                $this->eventDispatcher->dispatch(new BeforeMiddleware($middleware, $request));

                $response = null;
                try {
                    return $response = $middleware->process($request, $next);
                } finally {
                    $this->eventDispatcher->dispatch(new AfterMiddleware($middleware, $response));
                }
            }
        };
    }

    private function getCallbackMiddleware(callable $callback, ContainerInterface $container): MiddlewareInterface
    {
        return new class($callback, $container) implements MiddlewareInterface {
            /**
             * @var callable a PHP callback matching signature of [[MiddlewareInterface::process()]].
             */
            private $callback;
            private ContainerInterface $container;

            public function __construct(callable $callback, ContainerInterface $container)
            {
                $this->callback = $callback;
                $this->container = $container;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return (new Injector($this->container))->invoke($this->callback, [$request, $handler]);
            }
        };
    }
}
