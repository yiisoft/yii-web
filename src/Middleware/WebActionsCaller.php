<?php
namespace Yiisoft\Yii\Web\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Injector\Injector;

/**
 * WebActionsCaller maps a route like /post/{action} to methods of
 * a class instance specified named as "action" parameter.
 *
 * Dependencies are automatically injected into both method
 * and constructor based on types specified.
 */
class WebActionsCaller implements MiddlewareInterface
{
    private $class;
    private $container;

    public function __construct(string $class, ContainerInterface $container)
    {
        $this->class = $class;
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $controller = $this->container->get($this->class);
        $action = $request->getAttribute('action');
        if ($action === null) {
            throw new \RuntimeException('WebActionsCaller route does not contain action attribute.');
        }

        if (!method_exists($controller, $action)) {
            return $handler->handle($request);
        }

        return (new Injector($this->container))->invoke([$controller, $action], [$request, $handler]);
    }
}
