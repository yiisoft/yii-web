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
        reset($this->middlewares);
        return $this->handle($request);
    }

    /**
     * @internal Please use {@see dispatch()} or {@see process()} instead
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = current($this->middlewares);
        next($this->middlewares);
        if ($middleware === false) {
            if ($this->nextHandler !== null) {
                return $this->nextHandler->handle($request);
            }

            throw new \LogicException('Middleware stack exhausted');
        }

        return $middleware->process($request, $this);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $nextHandler): ResponseInterface
    {
        $this->nextHandler = $nextHandler;
        return $this->dispatch($request);
    }
}
