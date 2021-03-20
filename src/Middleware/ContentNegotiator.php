<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFormatterInterface;

/**
 * ContentNegotiator supports response format negotiation.
 */
final class ContentNegotiator implements MiddlewareInterface
{
    private array $contentFormatters;

    public function __construct(array $contentFormatters)
    {
        $this->checkFormatters($contentFormatters);
        $this->contentFormatters = $contentFormatters;
    }

    /**
     * @param array $contentFormatters
     */
    public function withContentFormatters(array $contentFormatters): self
    {
        $this->checkFormatters($contentFormatters);
        $new = clone $this;
        $new->contentFormatters = $contentFormatters;
        return $new;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if ($response instanceof DataResponse && !$response->hasResponseFormatter()) {
            $accepted = $request->getHeader('accept');

            foreach ($accepted as $accept) {
                foreach ($this->contentFormatters as $contentType => $formatter) {
                    if (strpos($accept, $contentType) !== false) {
                        return $response->withResponseFormatter($formatter);
                    }
                }
            }
        }

        return $response;
    }

    private function checkFormatters(array $contentFormatters): void
    {
        foreach ($contentFormatters as $contentType => $formatter) {
            if (!(is_string($contentType) && $formatter instanceof DataResponseFormatterInterface)) {
                throw new \RuntimeException('Invalid formatter type.');
            }
        }
    }
}
