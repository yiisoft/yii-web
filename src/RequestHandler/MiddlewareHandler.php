<?php

namespace Yiisoft\Yii\Web\RequestHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareHandler implements RequestHandlerInterface
{
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
}
