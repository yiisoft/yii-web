<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Data\Formatter;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Header;
use Yiisoft\Yii\Web\Data\DataResponse;
use Yiisoft\Yii\Web\Data\DataResponseFormatterInterface;

final class HtmlDataResponseFormatter implements DataResponseFormatterInterface
{
    /**
     * @var string the Content-Type header for the response
     */
    private string $contentType = 'text/html';

    /**
     * @var string the XML encoding.
     */
    private string $encoding = 'UTF-8';

    public function format(DataResponse $dataResponse): ResponseInterface
    {
        $data = $dataResponse->getData();
        $response = $dataResponse->getResponse();
        $response->getBody()->write((string)$data);

        return $response->withHeader(Header::CONTENT_TYPE, $this->contentType . '; charset=' . $this->encoding);
    }

    public function withEncoding(string $encoding): self
    {
        $formatter = clone $this;
        $formatter->encoding = $encoding;
        return $formatter;
    }

    public function withContentType(string $contentType): self
    {
        $formatter = clone $this;
        $this->contentType = $contentType;
        return $formatter;
    }
}
