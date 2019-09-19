<?php


namespace Yiisoft\Yii\Web\ErrorHandler;

/**
 * FriendlyExceptionInterface could be implemented by exception in order to provide friendly name and a solution for
 * fixing right it at the error screen
 */
interface FriendlyExceptionInterface
{
    public function getName(): string;
    public function getSolution(): ?string;
}
