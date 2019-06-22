<?php
namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\UrlGeneratorInterface;

final class Redirect implements MiddlewareInterface
{
    public const PERMANENT = 301;
    public const TEMPORARY = 302;

    private $uri;
    private $route;
    private $parameters = [];
    private $statusCode = self::PERMANENT;
    private $responseFactory;
    private $urlGenerator;

    private function __construct(ResponseFactoryInterface $responseFactory, UrlGeneratorInterface $urlGenerator)
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
        $this->statusCode = self::PERMANENT;
        return $this;
    }

    public function temporary(): self
    {
        $this->statusCode = self::TEMPORARY;
        return $this;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->route === null && $this->uri === null) {
            // TODO: should we throw exception instead?
            return $handler->handle($request);
        }

        $uri = $this->uri;
        if ($uri === null) {
            $uri = $this->urlGenerator->generate($this->route, $this->parameters);
        }

        return $this->responseFactory->createResponse($this->statusCode)
             ->withAddedHeader('Location', $uri);
    }
}
