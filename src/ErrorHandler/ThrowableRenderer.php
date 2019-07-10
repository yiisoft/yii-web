<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

use Psr\Http\Message\ServerRequestInterface;

abstract class ThrowableRenderer implements ThrowableRendererInterface
{
    private const SEVERITY_NAMES = [
        E_COMPILE_ERROR => 'PHP Compile Error',
        E_COMPILE_WARNING => 'PHP Compile Warning',
        E_CORE_ERROR => 'PHP Core Error',
        E_CORE_WARNING => 'PHP Core Warning',
        E_DEPRECATED => 'PHP Deprecated Warning',
        E_ERROR => 'PHP Fatal Error',
        E_NOTICE => 'PHP Notice',
        E_PARSE => 'PHP Parse Error',
        E_RECOVERABLE_ERROR => 'PHP Recoverable Error',
        E_STRICT => 'PHP Strict Warning',
        E_USER_DEPRECATED => 'PHP User Deprecated Warning',
        E_USER_ERROR => 'PHP User Error',
        E_USER_NOTICE => 'PHP User Notice',
        E_USER_WARNING => 'PHP User Warning',
        E_WARNING => 'PHP Warning',
    ];

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    protected function getThrowableName(\Throwable $t)
    {
        $name = get_class($t);

        if ($t instanceof \ErrorException && isset(self::SEVERITY_NAMES[$t->getSeverity()])) {
            $name .= ' (' . self::SEVERITY_NAMES[$t->getSeverity()] . ')';
        }

        return $name;
    }

    protected function convertThrowableToVerboseString(\Throwable $t): string
    {
        return $this->getThrowableName($t) . " with message '{$t->getMessage()}' \n\nin "
            . $t->getFile() . ':' . $t->getLine() . "\n\n"
            . "Stack trace:\n" . $t->getTraceAsString();
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }
}
