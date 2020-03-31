<?php

namespace Yiisoft\Yii\Web;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class WebResponseFactory implements WebResponseFactoryInterface
{
    protected StreamFactoryInterface $streamFactory;

    protected ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
        $this->responseFactory = $responseFactory;
    }

    public function createResponse($data = null, int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new WebResponse($data, $this->responseFactory, $this->streamFactory);
    }
}
