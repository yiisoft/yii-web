<?php
declare(strict_types=1);

namespace Yiisoft\Yii\Web\NetworkResolver;

use Psr\Http\Message\ServerRequestInterface;

class BasicNetworkResolver implements NetworkResolverInterface
{
    protected const SCHEME_HTTPS = 'https';

    /**
     * @var ServerRequestInterface|null
     */
    private $serverRequest;

    private $protocolHeaders = [];

    public function getRemoteIp(): string
    {
        $ip = $this->getServerRequest()->getServerParams()['REMOTE_ADDR'] ?? null;
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
     * User's request scheme
     */
    public function getRequestScheme(): string
    {
        $request = $this->getServerRequest();
        foreach ($this->protocolHeaders as $header => $data) {
            if (!$request->hasHeader($header)) {
                continue;
            }
            $headerValue = $request->getHeader($header)[0];
            if (is_callable($data)) {
                return call_user_func($data, $headerValue, $header, $request);
            }
            $headerValue = strtolower($headerValue);
            foreach ($data as $protocol => $acceptedValues) {
                if (!in_array($headerValue, $acceptedValues)) {
                    continue;
                }
                return $protocol;
            }
        }
        return $request->getUri()->getScheme();
    }

    /**
     * User's connection security
     */
    public function isSecureConnection(): bool
    {
        return $this->getRequestScheme() === self::SCHEME_HTTPS;
    }

    public function withServerRequest(ServerRequestInterface $serverRequest)
    {
        $new = clone $this;
        $new->serverRequest = $serverRequest;
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
        }
        $new->protocolHeaders[$header] = [];
        foreach ($protocolAndAcceptedValues as $protocol => $acceptedValues) {
            $new->protocolHeaders[$header][$protocol] = array_map('strtolower', (array)$acceptedValues);
        }
        return $new;
    }


    protected function getServerRequest(bool $throwIfNull = true): ?ServerRequestInterface
    {
        if ($this->serverRequest === null && $throwIfNull) {
            throw new \RuntimeException('The server request object is not set!');
        }
        return $this->serverRequest;
    }
}
