<?php

namespace Yiisoft\Yii\Web;

use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Http\Status;

class WebResponseFactory implements WebResponseFactoryInterface
{
    protected ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function createResponse($data = null, int $code = Status::OK, string $reasonPhrase = ''): WebResponse
    {
        return new WebResponse($data, $code, $reasonPhrase, $this->responseFactory);
    }
}
