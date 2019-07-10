<?php
namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Chain executes middlewares in the order they are added to constructor.
 *
 * Use if you need to execute middlewares before or after the middleware
 * you already have.
 */
final class Chain implements MiddlewareInterface
{
    private $middlewares;

    public function __construct(MiddlewareInterface ...$middlewares)
    {
        $this->middlewares = $middlewares;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        for ($i = count($this->middlewares) - 1; $i > 0; $i--) {
            $handler = $this->wrap($this->middlewares[$i], $handler);
        }
        return $this->middlewares[0]->process($request, $handler);
    }

    /**
     * Wraps handler by middlewares
     */
    private function wrap(MiddlewareInterface $middleware, RequestHandlerInterface $handler): RequestHandlerInterface
    {
        return new class($middleware, $handler) implements RequestHandlerInterface {
            private $middleware;
            private $handler;

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
