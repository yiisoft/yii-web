<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

class HtmlRenderer implements ErrorRenderer
{
    public function render(\Throwable $e): string
    {
        return '<h1>' . $this->encode($e->getMessage()) . '</h1>';
    }

    private function encode(string $text): string
    {
        return $text;
    }
}
