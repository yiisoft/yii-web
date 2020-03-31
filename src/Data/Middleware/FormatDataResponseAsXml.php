<?php

namespace Yiisoft\Yii\Web\Data\Middleware;

use Yiisoft\Yii\Web\Data\Formatter\XmlDataResponseFormatter;

final class FormatDataResponseAsXml extends FormatDataResponse
{
    public function __construct(XmlDataResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
