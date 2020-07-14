<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Config;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class InvalidListenerConfigurationException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'Invalid event listener configuration';
    }

    /**
     * @inheritDoc
     */
    public function getSolution(): ?string
    {
        return <<<SOLUTION
            Event listener has incorrect configuration. To meet EventConfigurator requirements a listener should be one of:
            - A closure
            - An array with an object under the 0 key and it's method under the 1 key
            - An array with a class name under the 0 key and it's method under the 1 key
            - An array with a string which can be converted to an object via your DI container under the 0 key and it's method under the 1 key

            If you are using classname or an alias string to be passed to a DI container please check if it is properly configured in the DI container.
        SOLUTION;

    }
}
