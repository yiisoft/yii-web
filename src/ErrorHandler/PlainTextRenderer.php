<?php


namespace Yiisoft\Web\ErrorHandler;


class PlainTextRenderer implements ErrorRenderer
{
    public function render(\Throwable $e): string
    {
        return $e->getMessage();
    }
}
