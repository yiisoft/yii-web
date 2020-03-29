<?php

namespace Yiisoft\Yii\Web\Middleware;

use Yiisoft\Yii\Web\Formatter\XmlResponseFormatter as XmlFormatter;

class XmlResponseFormatter extends ResponseFormatter
{
    public function __construct(XmlFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
