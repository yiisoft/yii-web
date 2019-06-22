<?php

namespace Yiisoft\Yii\Web\Emitter;

use Psr\Http\Message\ResponseInterface;

/**
 * Emitter takes PSR-8 ResponseInterface, formats and outputs it
 */
interface EmitterInterface
{
    public function emit(ResponseInterface $response): bool;
}
