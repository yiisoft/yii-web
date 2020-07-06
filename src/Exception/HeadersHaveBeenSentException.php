<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class HeadersHaveBeenSentException extends \Exception implements FriendlyExceptionInterface
{
    public function getName(): string
    {
        return 'HTTP headers have been sent';
    }
    public function getSolution(): ?string
    {
        \headers_sent($filename, $line);
        return "Headers already sent in {$filename} on line {$line}\n"
            . "Emitter can't send headers once the headers block has already been sent.";
    }
}
