<?php
namespace Yiisoft\Web\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Controller implements MiddlewareInterface
{
    private $class;
    private $method;
    private $container;

    public function __construct(string $class, string $method, ContainerInterface $container)
    {
        $this->class = $class;
        $this->method = $method;
        $this->container = $container;
    }


    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $controller = $this->container->get($this->class);
        // TODO: should we support method injection at all?
        return $controller->{$this->method}($request, $handler);
    }

    public static function __set_state(array $properties): self
    {
        return new self($properties['class'], $properties['method'], $properties['container']);
    }
}
