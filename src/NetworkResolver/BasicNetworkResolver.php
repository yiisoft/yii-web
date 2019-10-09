<?php
declare(strict_types=1);

namespace Yiisoft\Yii\Web\NetworkResolver;

use Psr\Http\Message\ServerRequestInterface;

class BasicNetworkResolver implements NetworkResolverInterface
{
    public function getRemoteIp(ServerRequestInterface $serverRequest): string
    {
        return $serverRequest->getServerParams()['REMOTE_ADDR'];
    }

    public function getUserIp(ServerRequestInterface $serverRequest): string
    {
        return $this->getRemoteIp($serverRequest);
    }

    public function getRequestScheme(ServerRequestInterface $serverRequest): string
    {
        return $serverRequest->getUri()->getScheme();
    }

    public function isSecureConnection(ServerRequestInterface $serverRequest): bool
    {
        return $this->getRequestScheme($serverRequest) === 'https';
    }
}
