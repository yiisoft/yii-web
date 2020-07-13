<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\ErrorHandler;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\HeaderHelper;
use Yiisoft\Http\Status;

/**
 * ErrorCatcher catches all throwables from the next middlewares and renders it
 * according to the content type passed by the client.
 */
final class ErrorCatcher implements MiddlewareInterface
{
    private array $renderers = [
        'application/json' => JsonRenderer::class,
        'application/xml' => XmlRenderer::class,
        'text/xml' => XmlRenderer::class,
        'text/plain' => PlainTextRenderer::class,
        'text/html' => HtmlRenderer::class,
        '*/*' => HtmlRenderer::class,
    ];

    private ResponseFactoryInterface $responseFactory;
    private ErrorHandler $errorHandler;
    private ContainerInterface $container;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ErrorHandler $errorHandler,
        ContainerInterface $container
    ) {
        $this->responseFactory = $responseFactory;
        $this->errorHandler = $errorHandler;
        $this->container = $container;
    }

    public function withRenderer(string $mimeType, string $rendererClass): self
    {
        $this->validateMimeType($mimeType);
        if (trim($rendererClass) === '') {
            throw new \InvalidArgumentException('The renderer class cannot be an empty string.');
        }
        $new = clone $this;
        $new->renderers[$this->normalizeMimeType($mimeType)] = $rendererClass;
        return $new;
    }

    /**
     * @param string[] $mimeTypes MIME types or, if not specified, all will be removed.
     */
    public function withoutRenderers(string ...$mimeTypes): self
    {
        $new = clone $this;
        if (count($mimeTypes) === 0) {
            $new->renderers = [];
            return $new;
        }
        foreach ($mimeTypes as $mimeType) {
            $this->validateMimeType($mimeType);
            unset($new->renderers[$this->normalizeMimeType($mimeType)]);
        }
        return $new;
    }

    private function handleException(\Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        $contentType = $this->getContentType($request);
        $renderer = $this->getRenderer(strtolower($contentType));
        if ($renderer !== null) {
            $renderer->setRequest($request);
        }
        $content = $this->errorHandler->handleCaughtThrowable($e, $renderer);
        $response = $this->responseFactory->createResponse(Status::INTERNAL_SERVER_ERROR)
            ->withHeader(Header::CONTENT_TYPE, $contentType);
        $response->getBody()->write($content);
        return $response;
    }

    private function getRenderer(string $contentType): ?ThrowableRendererInterface
    {
        if (isset($this->renderers[$contentType])) {
            return $this->container->get($this->renderers[$contentType]);
        }
        return null;
    }

    private function getContentType(ServerRequestInterface $request): string
    {
        try {
            foreach (HeaderHelper::getSortedAcceptTypesFromRequest($request) as $header) {
                if (array_key_exists($header, $this->renderers)) {
                    return $header;
                }
            }
        } catch (\InvalidArgumentException $e) {
            // The Accept header contains an invalid q factor
        }
        return '*/*';
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function validateMimeType(string $mimeType): void
    {
        if (strpos($mimeType, '/') === false) {
            throw new \InvalidArgumentException('Invalid mime type.');
        }
    }

    private function normalizeMimeType(string $mimeType): string
    {
        return strtolower($mimeType);
    }
}
