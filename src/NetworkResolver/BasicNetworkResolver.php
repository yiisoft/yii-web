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
        if ($this->serverRequest === null) {
            throw new \RuntimeException('ServerRequest doesn\'t set, see NetworkResolverInterface::withServerRequest()!');
        }
        return $this->serverRequest->getServerParams()['REMOTE_ADDR'];
    }

    public function getUserIp(): string
    {
        return $this->getRemoteIp();
    }

    public function withServerRequest(ServerRequestInterface $serverRequest)
    {
        $new = clone $this;
        $new->serverRequest = $serverRequest;
        return $new;
    }
}
