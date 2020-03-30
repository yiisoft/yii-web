<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Formatter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Yiisoft\Serializer\JsonSerializer;
use Yiisoft\Yii\Web\WebResponse;

final class JsonResponseFormatter implements ResponseFormatterInterface
{
    /**
     * @var string the Content-Type header for the response
     */
    private string $contentType = 'application/json';

    private JsonSerializer $jsonSerializer;

    public function __construct(JsonSerializer $jsonSerializer)
    {
        $this->jsonSerializer = $jsonSerializer;
    }

    public function format(WebResponse $webResponse): ResponseInterface
    {
        $content = $this->jsonSerializer->serialize($webResponse->getData());
        $response = $webResponse->getResponse();
        $response->getBody()->write($content);

        return $response->withHeader('Content-Type', $this->contentType);
    }
}
