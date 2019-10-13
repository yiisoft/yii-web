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

    private $secureProtocolHeaders = [];

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

    public function getRequestScheme(): string
    {
        $request = $this->getServerRequest();
        foreach ($this->secureProtocolHeaders as $header => $values) {
            if (!$request->hasHeader($header) || !in_array(strtolower($request->getHeader($header)[0]), $values)) {
                continue;
            }
            return self::SCHEME_HTTPS;
        }
        return $request->getUri()->getScheme();
    }

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
     * @TODO: currently https only, callback, http
     * @return static
     */
    public function withNewSecureProtocolHeader(string $header, array $valuesOfSecureProtocol)
    {
        $new = clone $this;
        if (count($valuesOfSecureProtocol) === 0) {
            throw new \RuntimeException('$valuesOfSecureProtocol cannot be an empty array!');
        }
        $new->secureProtocolHeaders[$header] = array_map('strtolower', $valuesOfSecureProtocol);
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
