<?php
namespace Yiisoft\Web\ErrorHandler;

interface ErrorRenderer
{
    public function render(\Throwable $e): string;
}
