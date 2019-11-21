<?php
namespace Yiisoft\Yii\Web\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\ErrorHandler\ErrorHandler;
use Yiisoft\Yii\Web\ErrorHandler\ThrowableRendererInterface;
use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;
use Yiisoft\Yii\Web\ErrorHandler\JsonRenderer;
use Yiisoft\Yii\Web\ErrorHandler\PlainTextRenderer;
use Yiisoft\Yii\Web\ErrorHandler\XmlRenderer;
use Yiisoft\Yii\Web\Helper\HeaderHelper;

/**
 * ErrorCatcher catches all throwables from the next middlewares and renders it
 * accoring to the content type passed by the client.
 */
final class ErrorCatcher implements MiddlewareInterface
{
    private $responseFactory;
    private $errorHandler;
    private $container;

    private $renderers = [
        'application/json' => JsonRenderer::class,
        'application/xml' => XmlRenderer::class,
        'text/xml' => XmlRenderer::class,
        'text/plain' => PlainTextRenderer::class,
        'text/html' => HtmlRenderer::class,
        '*/*' => HtmlRenderer::class,
    ];

    public function __construct(ResponseFactoryInterface $responseFactory, ErrorHandler $errorHandler, ContainerInterface $container)
    {
        $this->responseFactory = $responseFactory;
        $this->errorHandler = $errorHandler;
        $this->container = $container;
    }

    public function withAddedRenderer(string $mimeType, string $rendererClass): self
    {
        if (strlen($mimeType) === 0) {
            throw new \InvalidArgumentException('The mime type cannot be an empty string!');
        }
        if (strlen($rendererClass) === 0) {
            throw new \InvalidArgumentException('The renderer class cannot be an empty string!');
        }
        if (strpos($mimeType, '/') === false) {
            throw new \InvalidArgumentException('Invalid mime type!');
        }
        $new = clone $this;
        $new->renderers[strtolower($mimeType)] = $rendererClass;
        return $new;
    }

    /**
     * @param string... $mimeTypes MIME types or, if not specified, all will be removed.
     */
    public function withoutRenderers(string... $mimeTypes): self
    {
        $new = clone $this;
        if (count($mimeTypes) === 0) {
            $new->renderers = [];
            return $new;
        }
        foreach ($mimeTypes as $mimeType) {
            if (strlen($mimeType) === 0) {
                throw new \InvalidArgumentException('The mime type cannot be an empty string!');
            }
            unset($new->renderers[strtolower($mimeType)]);
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
        $response = $this->responseFactory->createResponse(500)
            ->withHeader('Content-type', $contentType);
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
            $acceptHeaders = HeaderHelper::getSortedAcceptTypesFromRequest($request);
            foreach ($acceptHeaders as $header) {
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
}
