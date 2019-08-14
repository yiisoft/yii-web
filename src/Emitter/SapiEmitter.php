<?php declare(strict_types=1);

namespace Yiisoft\Yii\Web\Emitter;

use Psr\Http\Message\ResponseInterface;

/**
 * SapiEmitter sends a response using PHP Server API
 */
final class SapiEmitter implements EmitterInterface
{
    private const NO_BODY_RESPONSE_CODES = [204, 205, 304];

    public function emit(ResponseInterface $response, bool $withoutBody = false): bool
    {
        $status = $response->getStatusCode();

        header_remove();
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

        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $status,
            ($reason !== '' ? ' ' . $reason : '')
        ), true, $status);

        if ($withoutBody === false && $this->shouldOutputBody($response)) {
            $contentLength = $response->getBody()->getSize();
            if ($response->hasHeader('Content-Length')) {
                $contentLengthHeader = $response->getHeader('Content-Length');
                $contentLength = array_shift($contentLengthHeader);
            }

            header(sprintf('Content-Length: %s', $contentLength), true, $status);

            echo $response->getBody();
        }

        return true;
    }

    private function shouldOutputBody(ResponseInterface $response): bool
    {
        return !\in_array($response->getStatusCode(), self::NO_BODY_RESPONSE_CODES, true);
    }
}
