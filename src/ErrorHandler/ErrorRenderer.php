<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

interface ErrorRenderer
{
    public function render(\Throwable $e): string;
}
