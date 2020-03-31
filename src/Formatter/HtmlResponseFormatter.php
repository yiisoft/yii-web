<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Formatter;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\Web\WebResponse;

final class HtmlResponseFormatter implements ResponseFormatterInterface
{
    /**
     * @var string the Content-Type header for the response
     */
    private string $contentType = 'text/html';

    /**
     * @var string the XML encoding.
     */
    private string $encoding = 'UTF-8';

    public function format(WebResponse $webResponse): ResponseInterface
    {
        $data = $webResponse->getData();
        $response = $webResponse->getResponse();
        $response->getBody()->write((string)$data);

        return $response->withHeader('Content-Type', $this->contentType . '; charset=' . $this->encoding);
    }

    public function setEncoding(string $encoding): void
    {
        $this->encoding = $encoding;
    }

    public function setContentType(string $contentType):void
    {
        $this->contentType = $contentType;
    }
}
