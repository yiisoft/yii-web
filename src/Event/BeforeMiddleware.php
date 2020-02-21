<?php

namespace Yiisoft\Yii\Web\Event;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

final class BeforeMiddleware
{
    private MiddlewareInterface $middleware;
    private ServerRequestInterface $request;

    public function __construct(MiddlewareInterface $middleware, ServerRequestInterface $request)
    {
        $this->middleware = $middleware;
        $this->request = $request;
    }

    public function getMiddleware(): MiddlewareInterface
    {
        return $this->middleware;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
