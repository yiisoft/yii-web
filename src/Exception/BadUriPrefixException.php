<?php

namespace Yiisoft\Yii\Web\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class BadUriPrefixException extends \Exception implements FriendlyExceptionInterface
{
    public function getName(): string
    {
        return 'Bad URI prefix';
    }
    public function getSolution(): ?string
    {
        return "Most likely you have specified the wrong URI prefix.\n"
            . "Make sure that path from the web address contains the specified prefix (immediately after the domain part).\n"
            . "The prefix value usually begins with a slash and must not end with a slash.";
    }
}
