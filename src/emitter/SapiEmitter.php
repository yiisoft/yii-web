<?php

declare(strict_types=1);

namespace yii\web\emitter;

use Psr\Http\Message\ResponseInterface;

/**
 * SapiEmitter sends response using PHP Server API.
 */
class SapiEmitter implements EmitterInterface
{
    public function emit(ResponseInterface $response): bool
    {
        $status = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            foreach ($values as $value) {
                header(sprintf(
                    '%s: %s',
                    $header,
                    $value
                ), $header !== 'Set-Cookie', $status);
            }
        }

        $reason = $response->getReasonPhrase();
        $status = $response->getStatusCode();

        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $status,
            ($reason ? ' '.$reason : '')
        ), true, $status);

        echo $response->getBody();

        return true;
    }
}
