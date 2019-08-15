<?php

namespace Yiisoft\Yii\Web\Emitter;

use Psr\Http\Message\ResponseInterface;

/**
 * Emitter takes PSR-7 ResponseInterface, formats and outputs it
 */
interface EmitterInterface
{
    /**
     * @param ResponseInterface $response
     * @param bool $withoutBody if body should be omitted
     * @return bool whether the response have been outputted successfully
     */
    public function emit(ResponseInterface $response, bool $withoutBody = false): bool;
}
