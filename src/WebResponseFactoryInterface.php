<?php

namespace Yiisoft\Yii\Web;

use Psr\Http\Message\ResponseInterface;

interface WebResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = '', $data = null): ResponseInterface;
}
