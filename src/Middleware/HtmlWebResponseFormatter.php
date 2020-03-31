<?php

namespace Yiisoft\Yii\Web\Middleware;

use Yiisoft\Yii\Web\Formatter\HtmlResponseFormatter;

final class HtmlWebResponseFormatter extends WebResponseFormatter
{
    public function __construct(HtmlResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
