<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class JsonBodyParser implements MiddlewareInterface
{
    private bool $throwException = true;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if ($contentType && strpos(strtolower($contentType), 'application/json') !== false) {
            $request = $request->withParsedBody(
                $this->parse($request->getBody()->getContents())
            );
        }

        return $handler->handle($request);
    }

    public function throwException(bool $value): self
    {
        $new = clone $this;
        $new->throwException = $value;
        return $new;
    }

    private function parse(string $body): array
    {
        try {
            $result = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            return is_array($result) ? $result : [];
        } catch (\JsonException $e) {
            if ($this->throwException) {
                throw new \InvalidArgumentException('Invalid JSON data in request body: ' . $e->getMessage());
            }
            return [];
        }
    }
}
