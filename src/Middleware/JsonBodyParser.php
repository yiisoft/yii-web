<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Header;

final class JsonBodyParser implements MiddlewareInterface
{
    private bool $assoc;
    private int $depth;
    private int $options;

    public function __construct(
        bool $assoc = true,
        int $depth = 512,
        int $options = JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_IGNORE
    ) {
        $this->assoc = $assoc;
        $this->depth = $depth;
        $this->options = $options;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine(Header::CONTENT_TYPE);

        if ($contentType && \strpos(\strtolower($contentType), 'application/json') !== false) {
            $request = $request->withParsedBody(
                $this->parse($request->getBody()->getContents())
            );
        }

        return $handler->handle($request);
    }

    /**
     * @return array|object|null
     */
    private function parse(string $rawBody)
    {
        $result = \json_decode($rawBody, $this->assoc, $this->depth, $this->options);
        if (\is_array($result) || \is_object($result)) {
            return $result;
        }
        return null;
    }
}
