<?php
namespace Yiisoft\Web\ErrorHandler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * ErrorHandler catches all throwables from the next middlewares and renders it
 * accoring to the content type passed by the client.
 */
class ErrorHandler implements MiddlewareInterface
{
    private $logger;
    private $responseFactory;

    private $renderers = [
        'application/json' => JsonRenderer::class,
        'application/xml' => XmlRenderer::class,
        'text/xml' => XmlRenderer::class,
        'text/plain' => PlainTextRenderer::class,
        'text/html' => HtmlRenderer::class,
    ];

    public function __construct(ResponseFactoryInterface $responseFactory, LoggerInterface $logger)
    {
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    private function handle(\Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        $this->log($e, $request);

        $contentType = $this->getContentType($request);
        $renderer = new $this->renderers[$contentType] ?? new HtmlRenderer();

        $response = $this->responseFactory->createResponse(500)
            ->withHeader('Content-type', $contentType);

        $response->getBody()->write($renderer->render($e));
        return $response;
    }

    private function log(\Throwable $e, ServerRequestInterface $request): void
    {
        $renderer = new PlainTextRenderer();
        $this->logger->error($renderer->render($e), [
            'throwable' => $e,
            'request' => $request,
        ]);
    }

    private function getContentType(ServerRequestInterface $request): string
    {
        $acceptHeaders = preg_split('~\s*,\s*~', $request->getHeaderLine('Accept'), PREG_SPLIT_NO_EMPTY);
        foreach ($acceptHeaders as $header) {
            if (array_key_exists($header, $this->renderers)) {
                return $header;
            }
        }
        return 'text/html';
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $e) {
            return $this->handle($e, $request);
        }
    }
}
