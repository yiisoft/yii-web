<?php

namespace Yiisoft\Yii\Web\Event;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

final class AfterMiddleware
{
    private MiddlewareInterface $middleware;
    private ?ResponseInterface $response;

    public function __construct(MiddlewareInterface $middleware, ?ResponseInterface $response)
    {
        $this->middleware = $middleware;
        $this->response = $response;
    }

    public function getMiddleware(): MiddlewareInterface
    {
        return $this->middleware;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
