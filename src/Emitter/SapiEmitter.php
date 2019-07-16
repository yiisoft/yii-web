<?php declare(strict_types=1);

namespace Yiisoft\Yii\Web\Emitter;

use Psr\Http\Message\ResponseInterface;

/**
 * SapiEmitter sends a response using PHP Server API
 */
final class SapiEmitter implements EmitterInterface
{
    private $withoutBody = false;

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
            ($reason !== '' ? ' ' . $reason : '')
        ), true, $status);

        if (!$this->withoutBody) {
            echo $response->getBody();
        }

        return true;
    }

    public function withoutBody(): EmitterInterface
    {
        $new = clone $this;
        $new->withoutBody = true;
        return $new;
    }
}
