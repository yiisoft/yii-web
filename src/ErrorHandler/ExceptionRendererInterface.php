<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

interface ExceptionRendererInterface
{
    public function render(\Throwable $e): string;
}
