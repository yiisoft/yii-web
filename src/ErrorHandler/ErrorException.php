<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\ErrorHandler;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

/**
 * ErrorException represents a PHP error.
 */
class ErrorException extends \ErrorException implements FriendlyExceptionInterface
{
    private const ERROR_NAMES = [
        E_ERROR => 'PHP Fatal Error',
        E_WARNING => 'PHP Warning',
        E_PARSE => 'PHP Parse Error',
        E_NOTICE => 'PHP Notice',
        E_CORE_ERROR => 'PHP Core Error',
        E_CORE_WARNING => 'PHP Core Warning',
        E_COMPILE_ERROR => 'PHP Compile Error',
        E_COMPILE_WARNING => 'PHP Compile Warning',
        E_USER_ERROR => 'PHP User Error',
        E_USER_WARNING => 'PHP User Warning',
        E_USER_NOTICE => 'PHP User Notice',
        E_STRICT => 'PHP Strict Warning',
        E_RECOVERABLE_ERROR => 'PHP Recoverable Error',
        E_DEPRECATED => 'PHP Deprecated Warning',
        E_USER_DEPRECATED => 'PHP User Deprecated Warning',
    ];

    public function __construct($message = '', $code = 0, $severity = 1, $filename = __FILE__, $lineno = __LINE__, \Exception $previous = null)
    {
        parent::__construct($message, $code, $severity, $filename, $lineno, $previous);
        $this->addXDebugTraceToFatalIfAvailable();
    }

    /**
     * Returns if error is one of fatal type.
     *
     * @param array $error error got from error_get_last()
     *
     * @return bool if error is one of fatal type
     */
    public static function isFatalError(array $error): bool
    {
        return isset($error['type']) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING], true);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return self::ERROR_NAMES[$this->getCode()] ?? 'Error';
    }

    /**
     * Fatal errors normally do not provide any trace making it harder to debug. In case XDebug is installed, we
     * can get a trace using xdebug_get_function_stack().
     *
     * @suppress PhanUndeclaredFunction
     */
    private function addXDebugTraceToFatalIfAvailable(): void
    {
        if (function_exists('xdebug_get_function_stack')) {
            // XDebug trace can't be modified and used directly with PHP 7
            // @see https://github.com/yiisoft/yii2/pull/11723
            $xDebugTrace = array_slice(array_reverse(xdebug_get_function_stack()), 1, -1);
            $trace = [];
            foreach ($xDebugTrace as $frame) {
                if (!isset($frame['function'])) {
                    $frame['function'] = 'unknown';
                }

                // XDebug < 2.1.1: http://bugs.xdebug.org/view.php?id=695
                if (!isset($frame['type']) || $frame['type'] === 'static') {
                    $frame['type'] = '::';
                } elseif ($frame['type'] === 'dynamic') {
                    $frame['type'] = '->';
                }

                // XDebug has a different key name
                if (isset($frame['params']) && !isset($frame['args'])) {
                    $frame['args'] = $frame['params'];
                }
                $trace[] = $frame;
            }

            $ref = new \ReflectionProperty('Exception', 'trace');
            $ref->setAccessible(true);
            $ref->setValue($this, $trace);
        }
    }

    public function getSolution(): ?string
    {
        return null;
    }
}
