<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

interface ErrorRendererInterface
{
    public function render(\Throwable $e): string;
}
