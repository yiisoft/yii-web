<?php

namespace Yiisoft\Yii\Web\Middleware;

use Yiisoft\Yii\Web\Formatter\XmlResponseFormatter;

final class XmlWebResponseFormatter extends WEbResponseFormatter
{
    public function __construct(XmlResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
