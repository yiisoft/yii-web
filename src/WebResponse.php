<?php

namespace Yiisoft\Yii\Web;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Yiisoft\Yii\Web\Formatter\ResponseFormatterInterface;

class WebResponse implements ResponseInterface
{
    private ResponseInterface $response;

    private $data;

    private ?StreamInterface $dataStream = null;

    private ?ResponseFormatterInterface $responseFormatter = null;

    public function __construct($data, int $code, string $reasonPhrase, ResponseFactoryInterface $responseFactory)
    {
        $this->response = $responseFactory->createResponse($code, $reasonPhrase);
        $this->data = $data;
    }

    public function getBody(): StreamInterface
    {
        if ($this->dataStream !== null) {
            return $this->dataStream;
        }

        if ($this->data === null) {
            return $this->dataStream = $this->response->getBody();
        }

        if ($this->responseFormatter !== null) {
            $this->response = $this->responseFormatter->format($this);
            return $this->dataStream = $this->response->getBody();
        }

        $data = $this->getData();
        if (is_string($data)) {
            $this->response->getBody()->write($data);
            return $this->dataStream = $this->response->getBody();
        }

        throw new \RuntimeException('Data must be a string value.');
    }

    public function getHeader($name): array
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine($name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function hasHeader($name): bool
    {
        return $this->response->hasHeader($name);
    }

    public function withAddedHeader($name, $value): WebResponse
    {
        $response = clone $this;
        $response->response = $this->response->withAddedHeader($name, $value);
        return $response;
    }

    public function withBody(StreamInterface $body): WebResponse
    {
        $response = clone $this;
        $response->response = $this->response->withBody($body);
        $response->dataStream = $body;
        return $response;
    }

    public function withHeader($name, $value): WebResponse
    {
        $response = clone $this;
        $response->response = $this->response->withHeader($name, $value);
        return $response;
    }

    public function withoutHeader($name): WebResponse
    {
        $response = clone $this;
        $response->response = $this->response->withoutHeader($name);
        return $response;
    }

    public function withProtocolVersion($version): WebResponse
    {
        $response = clone $this;
        $response->response = $this->response->withProtocolVersion($version);
        return $response;
    }

    public function withStatus($code, $reasonPhrase = ''): WebResponse
    {
        $response = clone $this;
        $response->response = $this->response->withStatus($code, $reasonPhrase);
        return $response;
    }

    public function withResponseFormatter(ResponseFormatterInterface $responseFormatter): WebResponse
    {
        $response = clone $this;
        $response->responseFormatter = $responseFormatter;
        return $response;
    }

    public function withData($data): WebResponse
    {
        $response = clone $this;
        $response->data = $data;

        return $response;
    }

    public function hasResponseFormatter(): bool
    {
        return $this->responseFormatter !== null;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getData()
    {
        if (is_callable($this->data)) {
            $this->data = ($this->data)();
        }
        return is_object($this->data) ? clone $this->data : $this->data;
    }
}
