<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Data\Middleware;

use Yiisoft\Yii\Web\Data\Formatter\JsonDataResponseFormatter;

final class FormatDataResponseAsJson extends FormatDataResponse
{
    public function __construct(JsonDataResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
