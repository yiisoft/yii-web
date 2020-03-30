<?php

namespace Yiisoft\Yii\Web\Formatter;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\Web\WebResponse;

interface ResponseFormatterInterface
{
    public function format(WebResponse $response): ResponseInterface;
}
