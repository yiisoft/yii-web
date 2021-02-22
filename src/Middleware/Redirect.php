<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;

final class Redirect implements MiddlewareInterface
{
    private ?string $uri = null;
    private ?string $route = null;
    private array $parameters = [];
    private int $statusCode = Status::MOVED_PERMANENTLY;
    private ResponseFactoryInterface $responseFactory;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(ResponseFactoryInterface $responseFactory, UrlGeneratorInterface $urlGenerator)
    {
        $this->responseFactory = $responseFactory;
        $this->urlGenerator = $urlGenerator;
    }

    public function toUrl(string $url): self
    {
        $new = clone $this;
        $new->uri = $url;
        return $new;
    }

    public function toRoute(string $name, array $parameters = []): self
    {
        $new = clone $this;
        $new->route = $name;
        $new->parameters = $parameters;
        return $new;
    }

    public function withStatus(int $code): self
    {
        $new = clone $this;
        $new->statusCode = $code;
        return $new;
    }

    public function permanent(): self
    {
        $new = clone $this;
        $new->statusCode = Status::MOVED_PERMANENTLY;
        return $new;
    }

    public function temporary(): self
    {
        $new = clone $this;
        $new->statusCode = Status::SEE_OTHER;
        return $new;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->route === null && $this->uri === null) {
            throw new \InvalidArgumentException('Either toUrl() or toRoute() should be used.');
        }

        $uri = $this->uri ?? $this->urlGenerator->generate($this->route, $this->parameters);

        return $this->responseFactory
            ->createResponse($this->statusCode)
            ->withAddedHeader('Location', $uri);
    }
}
