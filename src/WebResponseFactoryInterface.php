<?php

namespace Yiisoft\Yii\Web;

use Yiisoft\Http\Status;

interface WebResponseFactoryInterface
{
    public function createResponse($data = null, int $code = Status::OK, string $reasonPhrase = ''): WebResponse;
}
