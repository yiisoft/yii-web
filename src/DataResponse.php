<?php

namespace Yiisoft\Yii\Web;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Yiisoft\Yii\Web\Formatter\ResponseFormatterInterface;

/**
 * A wrapper around PSR-7 response that is assigned raw data to be formatted later using a formatter.
 *
 * For example, `['name' => 'Dmitry']` to be formatted to JSON using {@see \Yiisoft\Yii\Web\Formatter\JsonResponseFormatter}
 * when {@see DataResponse::getBody()} is called.
 */
class DataResponse implements ResponseInterface
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

    public function withAddedHeader($name, $value): DataResponse
    {
        $response = clone $this;
        $response->response = $this->response->withAddedHeader($name, $value);
        return $response;
    }

    public function withBody(StreamInterface $body): DataResponse
    {
        $response = clone $this;
        $response->response = $this->response->withBody($body);
        $response->dataStream = $body;
        return $response;
    }

    public function withHeader($name, $value): DataResponse
    {
        $response = clone $this;
        $response->response = $this->response->withHeader($name, $value);
        return $response;
    }

    public function withoutHeader($name): DataResponse
    {
        $response = clone $this;
        $response->response = $this->response->withoutHeader($name);
        return $response;
    }

    public function withProtocolVersion($version): DataResponse
    {
        $response = clone $this;
        $response->response = $this->response->withProtocolVersion($version);
        return $response;
    }

    public function withStatus($code, $reasonPhrase = ''): DataResponse
    {
        $response = clone $this;
        $response->response = $this->response->withStatus($code, $reasonPhrase);
        return $response;
    }

    public function withResponseFormatter(ResponseFormatterInterface $responseFormatter): DataResponse
    {
        $response = clone $this;
        $response->responseFormatter = $responseFormatter;
        return $response;
    }

    public function withData($data): DataResponse
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
