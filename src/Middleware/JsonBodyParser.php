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
    private const DEFAULT_FLAGS = JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_IGNORE;
    private bool $assoc = true;
    private int $depth = 512;
    private int $options = self::DEFAULT_FLAGS;
    private bool $throwException = true;

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

    public function withAssoc(): self
    {
        $new = clone $this;
        $new->assoc = true;
        return $new;
    }

    public function withoutAssoc(): self
    {
        $new = clone $this;
        $new->assoc = false;
        return $new;
    }

    public function withDepth(int $value): self
    {
        $new = clone $this;
        $new->depth = $value;
        return $new;
    }

    public function withOptions(int $value): self
    {
        $new = clone $this;
        $new->options = self::DEFAULT_FLAGS | $value;
        return $new;
    }

    public function withThrowException(): self
    {
        $new = clone $this;
        $new->throwException = true;
        return $new;
    }

    public function withoutThrowException(): self
    {
        $new = clone $this;
        $new->throwException = false;
        return $new;
    }

    /**
     * @return array|object|null
     */
    private function parse(string $rawBody)
    {
        $result = \json_decode(
            $rawBody,
            $this->assoc,
            $this->depth,
            $this->throwException
                ? $this->options
                : $this->options & ~JSON_THROW_ON_ERROR
        );
        if (\is_array($result) || \is_object($result)) {
            return $result;
        }
        return null;
    }
}
