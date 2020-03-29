<?php

namespace Yiisoft\Yii\Web\Middleware;

use Yiisoft\Yii\Web\Formatter\JsonResponseFormatter;

final class DeferredJsonResponseFormatter extends DeferredResponseFormatter
{
    public function __construct(JsonResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
