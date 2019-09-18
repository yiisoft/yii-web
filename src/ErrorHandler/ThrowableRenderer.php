<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

use Psr\Http\Message\ServerRequestInterface;

abstract class ThrowableRenderer implements ThrowableRendererInterface
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    protected function getThrowableName(\Throwable $t): string
    {
        $name = get_class($t);

        if ($t instanceof FriendlyExceptionInterface) {
            $name = $t->getName() . ' (' . $name . ')';
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
