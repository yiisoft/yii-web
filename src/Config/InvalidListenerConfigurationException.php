<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Config;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class InvalidListenerConfigurationException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    public function getName(): string
    {
        return 'Invalid event listener configuration';
    }

    public function getSolution(): ?string
    {
        return <<<SOLUTION
            Event listener has incorrect configuration. To meet EventConfigurator requirements a listener should be one of:
            - A closure.
            - [object, method] array.
            - [class name, method] array.
            - [DI container service ID, method] array.

            If you are using a classname or an alias string to be passed to a DI container please check if it is properly configured in the DI container.
        SOLUTION;
    }
}
