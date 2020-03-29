<?php

namespace Yiisoft\Yii\Web\Middleware;

use Yiisoft\Yii\Web\Formatter\HtmlResponseFormatter as HtmlFormatter;

final class HtmlResponseFormatter extends ResponseFormatter
{
    public function __construct(HtmlFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
