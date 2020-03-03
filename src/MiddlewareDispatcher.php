<?php

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
     * @var MiddlewareInterface[]
     */
    private array $middlewares = [];

    private RequestHandlerInterface $nextHandler;
    private ContainerInterface $container;

    /**
     * Contains a chain of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var RequestHandlerInterface|null stack of middleware
     */
    private ?RequestHandlerInterface $stack = null;

    public function __construct(
        ContainerInterface $container,
        RequestHandlerInterface $nextHandler = null
    ) {
        $this->container = $container;
        $this->nextHandler = $nextHandler ?? new NotFoundHandler($container->get(ResponseFactoryInterface::class));
    }

    /**
     * @param callable|MiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware($middleware): self
    {
        if (is_callable($middleware)) {
            $middleware = $this->getCallbackMiddleware($middleware, $this->container);
        }

        if (!$middleware instanceof MiddlewareInterface) {
            throw new \InvalidArgumentException(
                'Middleware should be either callable or MiddlewareInterface instance. ' . get_class(
                    $middleware
                ) . ' given.'
            );
        }

        $this->middlewares[] = $middleware;

        return $this;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process($request, $this->nextHandler);
    }

    private function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->stack === null) {
            $dispatcher = $this->container->get(EventDispatcherInterface::class);

            foreach ($this->middlewares as $middleware) {
                $handler = $this->wrap($middleware, $handler, $dispatcher);
            }
            $this->stack = $handler;
        }

        return $this->stack->handle($request);
    }

    /**
     * Wraps handler by middlewares
     */
    private function wrap(
        MiddlewareInterface $middleware,
        RequestHandlerInterface $handler,
        EventDispatcherInterface $dispatcher
    ): RequestHandlerInterface {
        return new class($middleware, $handler, $dispatcher) implements RequestHandlerInterface {
            private MiddlewareInterface $middleware;
            private RequestHandlerInterface $handler;
            private EventDispatcherInterface $dispatcher;

            public function __construct(
                MiddlewareInterface $middleware,
                RequestHandlerInterface $handler,
                EventDispatcherInterface $dispatcher
            ) {
                $this->middleware = $middleware;
                $this->handler = $handler;
                $this->dispatcher = $dispatcher;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->dispatcher->dispatch(new BeforeMiddleware($this->middleware, $request));

                $response = null;
                try {
                    return $response = $this->middleware->process($request, $this->handler);
                } finally {
                    $this->dispatcher->dispatch(new AfterMiddleware($this->middleware, $response));
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
