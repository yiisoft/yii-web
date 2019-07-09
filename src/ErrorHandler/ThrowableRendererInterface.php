<?php
namespace Yiisoft\Yii\Web\ErrorHandler;

use Psr\Http\Message\ServerRequestInterface;

interface ThrowableRendererInterface
{
    public function render(\Throwable $t): string;
    public function setRequest(ServerRequestInterface $request): void;
}
