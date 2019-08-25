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
final class MiddlewareDispatcher implements RequestHandlerInterface
{
    private $pointer = 0;

    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * @var RequestHandlerInterface
     */
    private $fallbackHandler;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        array $middlewares,
        ContainerInterface $container,
        RequestHandlerInterface $fallbackHandler = null
    ) {
        if ($middlewares === []) {
            throw new \InvalidArgumentException('Middlewares should be defined.');
        }

        $this->container = $container;

        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }

        $responseFactory = $container->get(ResponseFactoryInterface::class);

        $this->fallbackHandler = $fallbackHandler ?? new NotFoundHandler($responseFactory);
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

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->isLastMiddlewareCalled()) {
            return $this->fallbackHandler->handle($request);
        }

        return $this->middlewares[$this->pointer++]->process($request, $this);
    }

    /**
     * Prepare dispatcher to handle another request
     */
    public function reset(): void
    {
        $this->pointer = 0;
    }

    /**
     * Last middleware in the queue has been called on the request handler
     */
    private function isLastMiddlewareCalled(): bool
    {
        return $this->pointer === \count($this->middlewares);
    }
}
