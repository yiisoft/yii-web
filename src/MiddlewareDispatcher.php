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
final class MiddlewareDispatcher implements RequestHandlerInterface, MiddlewareInterface
{
    private $pointer = 0;

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

    public function __construct(
        array $middlewares,
        ContainerInterface $container,
        RequestHandlerInterface $nextHandler = null
    ) {
        if ($middlewares === []) {
            throw new \InvalidArgumentException('Middlewares should be defined.');
        }

        $this->container = $container;

        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }

        $responseFactory = $container->get(ResponseFactoryInterface::class);

        $this->nextHandler = $nextHandler ?? new NotFoundHandler($responseFactory);
    }

    private function addCallable(callable $callback): void
    {
        $this->middlewares[] = new Callback($callback, $this->container);
    }

    public function add($middleware): void
    {
        if (is_callable($middleware)) {
            $this->addCallable($middleware);
        } elseif ($middleware instanceof MiddlewareInterface) {
            $this->middlewares[] = $middleware;
        } else {
            throw new \InvalidArgumentException('Middleware should be either callable or MiddlewareInterface instance. ' . get_class($middleware) . ' given.');
        }
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $this->pointer = 0;
        return $this->handle($request);
    }

    /**
     * @internal Please use {@see dispatch()} instead
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->isLastMiddlewareCalled()) {
            if (!$this->nextHandler !== null) {
                return $this->nextHandler->handle($request);
            }

            throw new \LogicException('Middleware stack exhausted');
        }

        return $this->middlewares[$this->pointer++]->process($request, $this);
    }

    /**
     * Last middleware in the queue has been called on the request handler
     */
    private function isLastMiddlewareCalled(): bool
    {
        return $this->pointer === \count($this->middlewares);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $nextHandler): ResponseInterface
    {
        $this->nextHandler = $nextHandler;
        return $this->dispatch($request);
    }
}
