<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Formatter;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Serializer\JsonSerializer;
use Yiisoft\Yii\Web\WebResponse;

final class JsonResponseFormatter implements ResponseFormatterInterface
{
    /**
     * @var string the Content-Type header for the response
     */
    private string $contentType = 'application/json';

    private int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    public function format(WebResponse $webResponse): ResponseInterface
    {
        $jsonSerializer = new JsonSerializer($this->options);
        $content = $jsonSerializer->serialize($webResponse->getData());
        $response = $webResponse->getResponse();
        $response->getBody()->write($content);

        return $response->withHeader('Content-Type', $this->contentType);
    }

    public function setOptions(int $options): void
    {
        $this->options = $options;
    }

    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }
}
