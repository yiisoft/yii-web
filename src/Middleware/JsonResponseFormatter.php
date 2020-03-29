<?php

namespace Yiisoft\Yii\Web\Middleware;

use Yiisoft\Yii\Web\Formatter\JsonResponseFormatter as JsonFormatter;

final class JsonResponseFormatter extends ResponseFormatter
{
    public function __construct(JsonFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
