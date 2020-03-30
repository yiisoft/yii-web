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

    public function createResponse(int $code = 200, string $reasonPhrase = '', $data = null): ResponseInterface
    {
        return new WebResponse($data, $this->responseFactory->createResponse($code, $reasonPhrase), $this->streamFactory);
    }
}
