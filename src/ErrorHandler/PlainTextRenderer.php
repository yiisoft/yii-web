<?php

namespace Yiisoft\Yii\Web\ErrorHandler;

final class PlainTextRenderer extends ThrowableRenderer
{
    public function render(\Throwable $t): string
    {
        return 'An internal server error occurred';
    }

    public function renderVerbose(\Throwable $t): string
    {
        return $this->convertThrowableToVerboseString($t);
    }
}
