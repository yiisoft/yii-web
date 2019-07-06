<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

class XmlRenderer implements ErrorRendererInterface
{
    public function render(\Throwable $e): string
    {
        return $e->getMessage();
    }
}
