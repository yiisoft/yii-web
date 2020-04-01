<?php

namespace Yiisoft\Yii\Web\Data\Middleware;

use Yiisoft\Yii\Web\Data\Formatter\HtmlDataResponseFormatter;

final class FormatDataResponseAsHtml extends FormatDataResponse
{
    public function __construct(HtmlDataResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
