<?php
declare(strict_types=1);

namespace Yiisoft\Yii\Web\NetworkResolver;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Basic network resolver
 *
 * It can be used in the following cases:
 * - not required IP resolve to access the user's IP
 * - user's IP is already resolved (eg ngx_http_realip_module or similar)
 *
 * @package Yiisoft\Yii\Web\NetworkResolver
 */
class BasicNetworkResolver implements NetworkResolverInterface
{

    /**
     * @var ServerRequestInterface|null
     */
    private $baseServerRequest;

    private $protocolHeaders = [];

    public function getRemoteIp(): string
    {
        $ip = $this->getBaseServerRequest()->getServerParams()['REMOTE_ADDR'] ?? null;
        if ($ip === null) {
            throw new \RuntimeException('Remote IP is not available!');
        }
        return (string)$ip;
    }

    public function getUserIp(): string
    {
        return $this->getRemoteIp();
    }

    /**
     * User's connection security
     */
    public function isSecureConnection(): bool
    {
        return $this->getRequestScheme() === 'https';
    }

    public function withServerRequest(ServerRequestInterface $serverRequest)
    {
        $new = clone $this;
        $new->baseServerRequest = $serverRequest;
        return $new;
    }

    /**
     * @TODO: documentation
     * @param callable|array $protocolAndAcceptedValues
     * @return static
     */
    public function withNewProtocolHeader(string $header, $protocolAndAcceptedValues)
    {
        $new = clone $this;
        if (is_callable($protocolAndAcceptedValues)) {
            $new->protocolHeaders[$header] = $protocolAndAcceptedValues;
        } elseif (!is_array($protocolAndAcceptedValues)) {
            throw new \RuntimeException('$protocolAndAcceptedValues is not array nor callable!');
        } elseif (is_array($protocolAndAcceptedValues) && count($protocolAndAcceptedValues) === 0) {
            throw new \RuntimeException('$protocolAndAcceptedValues cannot be an empty array!');
        } else {
            $new->protocolHeaders[$header] = [];
            foreach ($protocolAndAcceptedValues as $protocol => $acceptedValues) {
                if (!is_string($protocol)) {
                    throw new \RuntimeException('The protocol must be type of string!');
                }
                $new->protocolHeaders[$header][$protocol] = array_map('strtolower', (array)$acceptedValues);
            }
        }
        return $new;
    }

    protected function getBaseServerRequest(bool $throwIfNull = true): ?ServerRequestInterface
    {
        if ($this->baseServerRequest === null && $throwIfNull) {
            throw new \RuntimeException('The server request object is not set!');
        }
        return $this->baseServerRequest;
    }

    public function getServerRequest(): ServerRequestInterface
    {
        $request = $this->getBaseServerRequest();
        $newScheme = null;
        foreach ($this->protocolHeaders as $header => $data) {
            if (!$request->hasHeader($header)) {
                continue;
            }
            $headerValues = $request->getHeader($header);
            if (is_callable($data)) {
                $newScheme = call_user_func($data, $headerValues, $header, $request);
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
        return $request;
    }
}
