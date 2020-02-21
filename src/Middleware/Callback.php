<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Injector\Injector;

final class Callback implements MiddlewareInterface
{
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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return (new Injector($this->container))->invoke($this->callback, [$request, $handler]);
    }
}
