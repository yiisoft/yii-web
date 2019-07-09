<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

/**
 * Formats exception into XML string
 */
class XmlRenderer implements ExceptionRendererInterface
{
    public function render(\Throwable $e): string
    {
        $out = '<' . '?xml version="1.0" encoding="UTF-8" standalone="yes" ?' . ">\n";
        $out .= "<error>\n";
        $out .= $this->tag('type', get_class($e));
        $out .= $this->tag('message', $this->cdata($e->getMessage()));
        $out .= $this->tag('code', $this->cdata($e->getCode()));
        $out .= $this->tag('file', $e->getFile());
        $out .= $this->tag('line', $e->getLine());
        $out .= $this->tag('trace', $e->getTraceAsString());
        $out .= '</error>';
        return $out;
    }

    private function tag(string $name, string $value): string
    {
        return "<$name>" . $value . "</$name>\n";
    }

    private function cdata(string $value): string
    {
        return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $value) . ']]>';
    }
}
