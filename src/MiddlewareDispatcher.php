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

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(array $middlewares, ContainerInterface $container, RequestHandlerInterface $fallbackHandler = null)
    {
        if ($middlewares === []) {
            throw new \InvalidArgumentException('Middlewares should be defined.');
        }

        $this->container = $container;

        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }

        /* @var \Psr\Http\Message\ResponseFactoryInterface $responseFactory */
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
        // Last middleware in the queue has called on the request handler
        if (\count($this->middlewares) === 0) {
            return $this->fallbackHandler->handle($request);
        }

        return array_shift($this->middlewares)->process($request, $this);
    }
}
