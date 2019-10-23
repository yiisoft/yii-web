<?php


namespace Yiisoft\Yii\Web\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Basic network resolver
 *
 * It can be used in the following cases:
 * - not required IP resolve to access the user's IP
 * - user's IP is already resolved (eg `ngx_http_realip_module` or similar)
 *
 * @package Yiisoft\Yii\Web\Middleware
 */
class BasicNetworkResolver implements MiddlewareInterface
{
    private const DEFAULT_PROTOCOL_AND_ACCEPTABLE_VALUES = [
        'http' => ['http'],
        'https' => ['https', 'on'],
    ];

    private $protocolHeaders = [];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $newScheme = null;
        foreach ($this->protocolHeaders as $header => $data) {
            if (!$request->hasHeader($header)) {
                continue;
            }
            $headerValues = $request->getHeader($header);
            if (is_callable($data)) {
                $newScheme = call_user_func($data, $headerValues, $header, $request);
                if ($newScheme === null) {
                    continue;
                }
                if (!is_string($newScheme)) {
                    throw new \RuntimeException('The scheme is neither string nor null!');
                }
                if (strlen($newScheme) === 0) {
                    throw new \RuntimeException('The scheme cannot be an empty string!');
                }
                break;
            }
            $headerValue = strtolower($headerValues[0]);
            foreach ($data as $protocol => $acceptedValues) {
                if (!in_array($headerValue, $acceptedValues)) {
                    continue;
                }
                $newScheme = $protocol;
                break 2;
            }
        }
        $uri = $request->getUri();
        if ($newScheme !== null && $newScheme !== $uri->getScheme()) {
            $request = $request->withUri($uri->withScheme($newScheme));
        }
        return $handler->handle($request);
    }

    /**
     * With added header to check for determining whether the connection is made via HTTP or HTTPS (or any protocol).
     *
     * The match of header names and values is case-insensitive.
     * It's not advisable to put insecure/untrusted headers here.
     *
     * Accepted types of values:
     * - NULL (default): {{DEFAULT_PROTOCOL_AND_ACCEPTABLE_VALUES}}
     * - callable: custom function for getting the protocol
     * ```php
     * ->withProtocolHeader('x-forwarded-proto', function(array $values, string $header, ServerRequestInterface $request) {
     *   return $values[0] === 'https' ? 'https' : 'http';
     *   return null;     // If it doesn't make sense.
     * });
     * ```
     * - array: The array keys are protocol string and the array value is a list of header values that indicate the protocol.
     * ```php
     * ->withProtocolHeader('x-forwarded-proto', [
     *   'http' => ['http'],
     *   'https' => ['https']
     * ]);
     * ```
     *
     * @param callable|array|null $values
     * @return static
     * @see DEFAULT_PROTOCOL_AND_ACCEPTABLE_VALUES
     */
    public function withAddedProtocolHeader(string $header, $values = null)
    {
        $new = clone $this;
        $header = strtolower($header);
        if ($values === null) {
            $new->protocolHeaders[$header] = self::DEFAULT_PROTOCOL_AND_ACCEPTABLE_VALUES;
            return $new;
        }
        if (is_callable($values)) {
            $new->protocolHeaders[$header] = $values;
            return $new;
        }
        if (!is_array($values)) {
            throw new \RuntimeException('Accepted values is not array nor callable!');
        }
        if (count($values) === 0) {
            throw new \RuntimeException('Accepted values cannot be an empty array!');
        }
        $new->protocolHeaders[$header] = [];
        foreach ($values as $protocol => $acceptedValues) {
            if (!is_string($protocol)) {
                throw new \RuntimeException('The protocol must be type of string!');
            }
            if (strlen($protocol) === 0) {
                throw new \RuntimeException('The protocol cannot be an empty string!');
            }
            $new->protocolHeaders[$header][$protocol] = array_map('strtolower', (array)$acceptedValues);
        }
        return $new;
    }

    /**
     * @return static
     */
    public function withoutProtocolHeader(string $header)
    {
        $new = clone $this;
        unset($new->protocolHeaders[strtolower($header)]);
        return $new;
    }

    /**
     * @return static
     */
    public function withoutProtocolHeaders(?array $headers = null)
    {
        $new = clone $this;
        if ($headers === null) {
            $new->protocolHeaders = [];
        } else {
            foreach ($headers as $header) {
                $new = $new->withoutProtocolHeader($header);
            }
        }
        return $new;
    }
}
