<?php

namespace Yiisoft\Yii\Web\Middleware;

use Yiisoft\Yii\Web\Formatter\JsonResponseFormatter;

final class JsonWebResponseFormatter extends WebResponseFormatter
{
    public function __construct(JsonResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
