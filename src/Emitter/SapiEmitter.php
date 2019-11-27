<?php declare(strict_types=1);

namespace Yiisoft\Yii\Web\Emitter;

use Psr\Http\Message\ResponseInterface;

/**
 * SapiEmitter sends a response using PHP Server API
 */
final class SapiEmitter implements EmitterInterface
{
    private const NO_BODY_RESPONSE_CODES = [100, 101, 102, 204, 205, 304];
    private const DEFAULT_BUFFER_SIZE = 8388608; // 8MB

    private $bufferSize;

    public function __construct(?int $bufferSize = null)
    {
        if ($bufferSize !== null && $bufferSize <= 0) {
            throw new \InvalidArgumentException('Buffer size must be greater than zero');
        }
        $this->bufferSize = $bufferSize ?? self::DEFAULT_BUFFER_SIZE;
    }

    public function emit(ResponseInterface $response, bool $withoutBody = false): bool
    {
        $status = $response->getStatusCode();
        $withoutBody = $withoutBody || !$this->shouldOutputBody($response);
        $withoutContentLength = $withoutBody || $response->hasHeader('Transfer-Encoding');

        // we can't replace headers if they are already sent
        if (!headers_sent()) {
            header_remove();
            // send HTTP Status-Line
            header(sprintf(
                'HTTP/%s %d %s',
                $response->getProtocolVersion(),
                $status,
                $response->getReasonPhrase()
            ), true, $status);
            // filter headers
            $headers = $withoutContentLength
                ? $response->withoutHeader('Content-Length')
                           ->getHeaders()
                : $response->getHeaders();
            // send headers
            foreach ($headers as $header => $values) {
                $replaceFirst = strtolower($header) !== 'set-cookie';
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $header, $value), $replaceFirst);
                    $replaceFirst = false;
                }
            }
        } else {
            $withoutContentLength = false;
        }

        if (!$withoutBody) {
            if (!$withoutContentLength && !$response->hasHeader('Content-Length')) {
                $contentLength = $response->getBody()->getSize();
                if ($contentLength !== null) {
                    header('Content-Length: ' . $contentLength, true);
                }
            }

            $this->emitBody($response);
        }

        return true;
    }

    private function emitBody(ResponseInterface $response): void
    {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        while (!$body->eof()) {
            echo $body->read($this->bufferSize);
            \flush();
        }
    }

    private function shouldOutputBody(ResponseInterface $response)
    {
        if (\in_array($response->getStatusCode(), self::NO_BODY_RESPONSE_CODES, true)) {
            return false;
        }
        // check if body is empty
        $body = $response->getBody();
        $size = $body->getSize();
        if ($size !== null) {
            return $size > 0;
        }
        if ($body->isSeekable()) {
            $body->rewind();
            $byte = $body->read(1);
            if ($byte === '' || $body->eof()) {
                return false;
            }
        }
        return true;
    }
}
