<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

class PlainTextRenderer extends ThrowableRenderer
{
    public function render(\Throwable $t): string
    {
        return  $this->convertThrowableToVerboseString($t);
    }
}
