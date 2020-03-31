<?php

namespace Yiisoft\Yii\Web\Middleware;

use Yiisoft\Yii\Web\Formatter\HtmlResponseFormatter;

final class FormatWebResponseAsHtml extends FormatWebResponse
{
    public function __construct(HtmlResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
