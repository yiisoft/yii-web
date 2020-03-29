<?php

namespace Yiisoft\Yii\Web\Middleware;

use Yiisoft\Yii\Web\Formatter\HtmlResponseFormatter;

final class DeferredHtmlResponseFormatter extends DeferredResponseFormatter
{
    public function __construct(HtmlResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
