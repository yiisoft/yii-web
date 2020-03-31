<?php

namespace Yiisoft\Yii\Web\Formatter;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\Web\DataResponse;

interface ResponseFormatterInterface
{
    public function format(DataResponse $response): ResponseInterface;
}
