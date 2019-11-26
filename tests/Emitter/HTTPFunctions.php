<?php
/**
 * This class used to override some header*() functions and http_response_code()
 *
 * We put these into the Yii namespace, so that Yiisoft\Yii\Web\Emitter will use these versions of header*() and
 * http_response_code() when we test its output.
 */

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Emitter;

class HTTPFunctions
{
    /** @var string[][] */
    private static $headers = [];
    /** @var int */
    private static $responseCode = 200;

    /**
     * Reset state
     */
    public static function reset(): void
    {
        self::$headers = [];
        self::$responseCode = 200;
    }

    /**
     * Send a raw HTTP header
     */
    public static function header(string $string, bool $replace = true, ?int $http_response_code = null): void
    {
        if (substr($string, 0, 5) !== 'HTTP/') {
            $header = strtolower(explode(':', $string)[0]);
            if ($replace || !key_exists($header, self::$headers)) {
                self::$headers[$header] = [];
            }
            self::$headers[$header][] = $string;
        }
        if ($http_response_code !== null) {
            self::$responseCode = $http_response_code;
        }
    }

    /**
     * Remove previously set headers
     */
    public static function header_remove(?string $name = null): void
    {
        if ($name === null) {
            self::$headers = [];
        } else {
            $name = strtolower($name);
            if (key_exists($name, self::$headers)) {
                unset(self::$headers[$name]);
            }
        }
    }

    /**
     * Returns a list of response headers sent
     *
     * @return string[]
     */
    public static function headers_list(): array
    {
        $result = [];
        foreach (self::$headers as $values) {
            foreach ($values as $header) {
                $result[] = $header;
            }
        }
        return $result;
    }

    /**
     * Get or Set the HTTP response code
     */
    public static function http_response_code(?int $response_code = null): int
    {
        if ($response_code !== null) {
            self::$responseCode = $response_code;
        }
        return self::$responseCode;
    }

    /**
     * Check header is exists
     */
    public static function hasHeader(string $header): bool
    {
        $name = strtolower($header);
        return key_exists($name, self::$headers);
    }
}
