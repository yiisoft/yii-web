<?php


namespace Yiisoft\Yii\Web\ErrorHandler;


/**
 * SolutionInterface could be implemented by exception in order to provide solution for fixing it at the error screen
 */
interface SolutionInterface
{
    public function getSolution(): string;
}
