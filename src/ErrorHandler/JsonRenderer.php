<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

/**
 * Formats exception into JSON string
 */
class JsonRenderer implements ErrorRendererInterface
{
    public function render(\Throwable $e): string
    {
        return json_encode([
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
