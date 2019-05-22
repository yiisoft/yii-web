<?php

namespace yii\web\router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MatchingResult implements MiddlewareInterface
{
    private $success;

    /**
     * @var Route
     */
    private $route;
    private $parameters = [];
    private $methods;

    private function __construct()
    {
    }

    public static function fromSuccess(Route $route, array $parameters): self
    {
        $new = new self();
        $new->success = true;
        $new->route = $route;
        $new->parameters = $parameters;
        return $new;
    }

    public static function fromFailure(array $methods): self
    {
        $new = new static();
        $new->methods = $methods;
        $new->success = false;
        return $new;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function route(): ?Route
    {
        return $this->route;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->success === false) {
            return $handler->handle($request);
        }

        return $this->route->process($request, $handler);
    }
}
