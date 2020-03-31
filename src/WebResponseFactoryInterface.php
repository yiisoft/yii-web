<?php

namespace Yiisoft\Yii\Web;

use Psr\Http\Message\ResponseInterface;

interface WebResponseFactoryInterface
{
    public function createResponse($data = null, int $code = 200, string $reasonPhrase = ''): ResponseInterface;
}
