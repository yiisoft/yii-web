<?php

namespace Yiisoft\Yii\Web\Data;

use Psr\Http\Message\ResponseInterface;

interface DataResponseFormatterInterface
{
    public function format(DataResponse $response): ResponseInterface;
}
