<?php

namespace Yiisoft\Yii\Web\Data;

use Yiisoft\Http\Status;

interface DataResponseFactoryInterface
{
    public function createResponse($data = null, int $code = Status::OK, string $reasonPhrase = ''): DataResponse;
}
