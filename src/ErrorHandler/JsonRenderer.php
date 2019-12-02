<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

/**
 * Formats exception into JSON string
 */
final class JsonRenderer extends ThrowableRenderer
{
    public function render(\Throwable $t): string
    {
        return json_encode([
            'message' => 'An internal server error occurred',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function renderVerbose(\Throwable $t): string
    {
        return json_encode([
            'type' => get_class($t),
            'message' => $t->getMessage(),
            'code' => $t->getCode(),
            'file' => $t->getFile(),
            'line' => $t->getLine(),
            'trace' => $t->getTrace(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
