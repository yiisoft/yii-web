<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Config;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class InvalidEventConfigurationFormatException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'Configuration format passed to EventConfigurator is invalid';
    }

    /**
     * @inheritDoc
     */
    public function getSolution(): ?string
    {
        return <<<SOLUTION
            EventConfigurator accepts an array in the following format:
                [
                    'eventName1' => [\$listener1, \$listener2, ...],
                    'eventName2' => [\$listener3, \$listener4, ...],
                    ...
                ]
        SOLUTION;
    }
}
