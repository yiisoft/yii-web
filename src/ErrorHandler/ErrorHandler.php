<?php

namespace Yiisoft\Yii\Web\ErrorHandler;

use Psr\Log\LoggerInterface;

final class ErrorHandler
{
    /**
     * @var int the size of the reserved memory. A portion of memory is pre-allocated so that
     * when an out-of-memory issue occurs, the error handler is able to handle the error with
     * the help of this reserved memory. If you set this value to be 0, no memory will be reserved.
     * Defaults to 256KB.
     */
    private $memoryReserveSize = 262144;

    private $memoryReserve;

    private $logger;

    private $defaultRenderer;

    private $exposeDetails = true;

    public function __construct(LoggerInterface $logger, ThrowableRendererInterface $defaultRenderer)
    {
        $this->logger = $logger;
        $this->defaultRenderer = $defaultRenderer;
    }

    /**
     * Handles PHP execution errors such as warnings and notices.
     *
     * This method is used as a PHP error handler. It will raise an [[\ErrorException]].
     *
     * @param int $severity the level of the error raised.
     * @param string $message the error message.
     * @param string $file the filename that the error was raised in.
     * @param int $line the line number the error was raised at.
     *
     * @throws ErrorException
     */
    public function handleError(int $severity, string $message, string $file, int $line): void
    {
        if (!(error_reporting() & $severity)) {
            // This error code is not included in error_reporting
            return;
        }

        // in case error appeared in __toString method we can't throw any exception
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_shift($trace);
        foreach ($trace as $frame) {
            if ($frame['function'] === '__toString') {
                trigger_error($message, $severity);
                return;
            }
        }

        throw new ErrorException($message, $severity, $severity, $file, $line);
    }

    /**
     * Handle throwable and return output
     *
     * @param \Throwable $t
     * @param ThrowableRendererInterface|null $renderer
     * @return string
     */
    public function handleCaughtThrowable(\Throwable $t, ThrowableRendererInterface $renderer = null): string
    {
        if ($renderer === null) {
            $renderer = $this->defaultRenderer;
        }

        try {
            $this->log($t);
            return $this->exposeDetails ? $renderer->renderVerbose($t) : $renderer->render($t);
        } catch (\Throwable $t) {
            return nl2br($t);
        }
    }

    /**
     * Handle throwable, echo output and exit
     *
     * @param \Throwable $t
     */
    public function handleThrowable(\Throwable $t): void
    {
        // disable error capturing to avoid recursive errors while handling exceptions
        $this->unregister();

        // set preventive HTTP status code to 500 in case error handling somehow fails and headers are sent
        http_response_code(500);

        echo $this->handleCaughtThrowable($t);
        exit(1);
    }

    /**
     * Register this error handler.
     */
    public function register(): void
    {
        $this->disableDisplayErrors();
        set_exception_handler([$this, 'handleThrowable']);
        set_error_handler([$this, 'handleError']);

        if ($this->memoryReserveSize > 0) {
            $this->memoryReserve = str_repeat('x', $this->memoryReserveSize);
        }
        register_shutdown_function([$this, 'handleFatalError']);
    }

    private function disableDisplayErrors(): void
    {
        if (function_exists('ini_set')) {
            ini_set('display_errors', '0');
        }
    }

    /**
     * Unregisters this error handler by restoring the PHP error and exception handlers.
     */
    public function unregister(): void
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public function handleFatalError(): void
    {
        unset($this->memoryReserve);
        $error = error_get_last();
        if ($error !== null && ErrorException::isFatalError($error)) {
            $exception = new ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
            $this->handleThrowable($exception);
            exit(1);
        }
    }

    private function log(\Throwable $t/*, ServerRequestInterface $request*/): void
    {
        $renderer = new PlainTextRenderer();
        $this->logger->error($renderer->renderVerbose($t), [
            'throwable' => $t,
            //'request' => $request,
        ]);
    }

    public function setExposeDetails(bool $exposeDetails): void
    {
        $this->exposeDetails = $exposeDetails;
    }
}
