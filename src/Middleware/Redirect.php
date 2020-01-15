<?php
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
    private $uri;
    private $route;
    private $parameters = [];
    private $statusCode = Status::MOVED_PERMANENTLY;
    private $responseFactory;
    private $urlGenerator;

    public function __construct(ResponseFactoryInterface $responseFactory, UrlGeneratorInterface $urlGenerator)
    {
        $this->responseFactory = $responseFactory;
        $this->urlGenerator = $urlGenerator;
    }

    public function toUrl(string $url): self
    {
        $this->uri = $url;
        return $this;
    }

    public function toRoute(string $name, array $parameters = []): self
    {
        $this->route = $name;
        $this->parameters = $parameters;
        return $this;
    }

    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function permanent(): self
    {
        $this->statusCode = Status::MOVED_PERMANENTLY;
        return $this;
    }

    public function temporary(): self
    {
        $this->statusCode = Status::TEMPORARY_REDIRECT;
        return $this;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->route === null && $this->uri === null) {
            throw new \InvalidArgumentException('Either toUrl() or toRoute() should be used.');
        }

        $uri = $this->uri;
        if ($uri === null) {
            $uri = $this->urlGenerator->generate($this->route, $this->parameters);
        }

        return $this->responseFactory->createResponse($this->statusCode)
             ->withAddedHeader('Location', $uri);
    }
}
