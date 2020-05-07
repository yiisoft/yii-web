<?php

namespace Yiisoft\Yii\Web\ErrorHandler;

final class PlainTextRenderer extends ThrowableRenderer
{
    public function render(\Throwable $t, string $template = 'error', string $customPath = null): string
    {
        return 'An internal server error occurred';
    }

    public function renderVerbose(\Throwable $t, string $template = 'exception', string $customPath = null): string
    {
        return $this->convertThrowableToVerboseString($t);
    }
}
