<?php
declare(strict_types=1);

namespace Yiisoft\Yii\Web\NetworkResolver;

use Psr\Http\Message\ServerRequestInterface;

class BasicNetworkResolver implements NetworkResolverInterface
{
    /**
     * @var ServerRequestInterface|null
     */
    private $serverRequest;

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
        return $this->getServerRequest()->getUri()->getScheme();
    }

    public function isSecureConnection(): bool
    {
        return $this->getRequestScheme() === 'https';
    }

    public function setServerRequest(ServerRequestInterface $serverRequest)
    {
        $this->serverRequest = $serverRequest;
        return $this;
    }

    public function withServerRequest(ServerRequestInterface $serverRequest)
    {
        $new = clone $this;
        $new->setServerRequest($serverRequest);
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
