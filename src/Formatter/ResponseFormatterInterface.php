<?php

namespace Yiisoft\Yii\Web\Formatter;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\Web\Response;

interface ResponseFormatterInterface
{
    public function format(Response $response): ResponseInterface;
}
