<?php

namespace Yiisoft\Yii\Web\Data;

use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Http\Status;

class DataResponseFactory implements DataResponseFactoryInterface
{
    protected ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function createResponse($data = null, int $code = Status::OK, string $reasonPhrase = ''): DataResponse
    {
        return new DataResponse($data, $this->responseFactory->createResponse($code, $reasonPhrase));
    }
}
