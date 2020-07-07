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
    private bool $assoc = true;
    private bool $throwException = true;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine(Header::CONTENT_TYPE);

        if ($contentType && strpos(strtolower($contentType), 'application/json') !== false) {
            $request = $request->withParsedBody(
                $this->parse($request->getBody()->getContents())
            );
        }

        return $handler->handle($request);
    }

    public function withAssoc(bool $value): self
    {
        $new = clone $this;
        $new->assoc = $value;
        return $new;
    }

    public function withThrowException(bool $value): self
    {
        $new = clone $this;
        $new->throwException = $value;
        return $new;
    }

    /**
     * @return mixed
     */
    private function parse(string $body)
    {
        try {
            $result = json_decode($body, $this->assoc, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_IGNORE);
            if (is_array($result) || is_object($result)) {
                return $result;
            }
        } catch (\JsonException $e) {
            if ($this->throwException) {
                throw new \InvalidArgumentException('Invalid JSON data in request body: ' . $e->getMessage());
            }
        }
        return null;
    }
}
