<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Data\Formatter;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Serializer\JsonSerializer;
use Yiisoft\Yii\Web\Data\DataResponse;
use Yiisoft\Yii\Web\Data\DataResponseFormatterInterface;

final class JsonDataResponseFormatter implements DataResponseFormatterInterface
{
    /**
     * @var string the Content-Type header for the response
     */
    private string $contentType = 'application/json';

    private int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    public function format(DataResponse $dataResponse): ResponseInterface
    {
        $jsonSerializer = new JsonSerializer($this->options);
        $content = $jsonSerializer->serialize($dataResponse->getData());
        $response = $dataResponse->getResponse();
        $response->getBody()->write($content);

        return $response->withHeader('Content-Type', $this->contentType);
    }

    public function withOptions(int $options): self
    {
        $formatter = clone $this;
        $formatter->options = $options;
        return $formatter;
    }

    public function withContentType(string $contentType): self
    {
        $formatter = clone $this;
        $formatter->contentType = $contentType;
        return $formatter;
    }
}
