<?php

namespace Yiisoft\Yii\Web;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;

interface WebResponseFactoryInterface
{
    public function createResponse($data = null, int $code = Status::OK, string $reasonPhrase = ''): ResponseInterface;
}
