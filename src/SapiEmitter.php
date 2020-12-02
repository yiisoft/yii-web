<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Yii\Web\Exception\HeadersHaveBeenSentException;

/**
 * SapiEmitter sends a response using PHP Server API
 */
final class SapiEmitter
{
    private const NO_BODY_RESPONSE_CODES = [
        Status::CONTINUE,
        Status::SWITCHING_PROTOCOLS,
        Status::PROCESSING,
        Status::NO_CONTENT,
        Status::RESET_CONTENT,
        Status::NOT_MODIFIED,
    ];

    private const DEFAULT_BUFFER_SIZE = 8_388_608; // 8MB

    private int $bufferSize;

    public function __construct(int $bufferSize = null)
    {
        if ($bufferSize !== null && $bufferSize <= 0) {
            throw new \InvalidArgumentException('Buffer size must be greater than zero');
        }
        $this->bufferSize = $bufferSize ?? self::DEFAULT_BUFFER_SIZE;
    }

    /**
     * @param ResponseInterface $response
     * @param bool $withoutBody
     *
     * @throws HeadersHaveBeenSentException
     *
     * @return bool
     */
    public function emit(ResponseInterface $response, bool $withoutBody = false): bool
    {
        $status = $response->getStatusCode();
        $withoutBody = $withoutBody || !$this->shouldOutputBody($response);
        $withoutContentLength = $withoutBody || $response->hasHeader('Transfer-Encoding');
        if ($withoutContentLength) {
            $response = $response->withoutHeader('Content-Length');
        }

        // we can't send headers if they are already sent
        if (headers_sent()) {
            throw new HeadersHaveBeenSentException();
        }
        header_remove();
        // send HTTP Status-Line
        header(\sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $status,
            $response->getReasonPhrase()
        ), true, $status);
        // send headers
        foreach ($response->getHeaders() as $header => $values) {
            $replaceFirst = \strtolower($header) !== 'set-cookie';
            foreach ($values as $value) {
                header("{$header}: {$value}", $replaceFirst);
                $replaceFirst = false;
            }
        }

        if (!$withoutBody) {
            if (!$withoutContentLength && !$response->hasHeader('Content-Length')) {
                $contentLength = $response->getBody()->getSize();
                if ($contentLength !== null) {
                    header("Content-Length: {$contentLength}", true);
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

    private function shouldOutputBody(ResponseInterface $response): bool
    {
        if (\in_array($response->getStatusCode(), self::NO_BODY_RESPONSE_CODES, true)) {
            return false;
        }
        // check if body is empty
        $body = $response->getBody();
        if (!$body->isReadable()) {
            return false;
        }
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
