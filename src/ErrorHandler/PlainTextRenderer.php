<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

class PlainTextRenderer implements ErrorRendererInterface
{
    public function render(\Throwable $e): string
    {
        return "Exception '" . get_class($e) . "' with message '{$e->getMessage()}' \n\nin "
            . $e->getFile() . ':' . $e->getLine() . "\n\n"
            . "Stack trace:\n" . $e->getTraceAsString();
    }
}
