<?php
namespace yii\web;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use yii\web\middleware\Callback;

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
    private $fallbackHanlder;

    public function __construct(array $middlewares, ResponseFactoryInterface $responseFactory, RequestHandlerInterface $fallbackHandler = null)
    {
        if ($middlewares === []) {
            throw new \InvalidArgumentException('Middlewares should be defined.');
        }

        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                $middleware = new Callback($middleware);
            }
            $this->middlewares[] = $middleware;
        }

        $this->fallbackHanlder = $fallbackHandler ?? new NotFoundHandler($responseFactory);
    }

    public function add(MiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Last middleware in the queue has called on the request handler
        if (\count($this->middlewares) === 0) {
            return $this->fallbackHanlder->handle($request);
        }

        return array_shift($this->middlewares)->process($request, $this);
    }
}
