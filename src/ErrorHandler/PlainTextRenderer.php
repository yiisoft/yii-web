<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

class PlainTextRenderer implements ErrorRendererInterface
{
    public function render(\Throwable $e): string
    {
        return $e->getMessage();
    }
}
