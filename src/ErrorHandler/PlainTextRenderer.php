<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

final class PlainTextRenderer extends ThrowableRenderer
{
    public function render(\Throwable $t): string
    {
        return  $this->convertThrowableToVerboseString($t);
    }
}
