<?php


namespace Yiisoft\Yii\Web\NetworkResolver;

use Psr\Http\Message\ServerRequestInterface;

interface NetworkResolverInterface
{
    public function getRemoteIp(): string;

    public function getUserIp(): string;

    /**
     * @return static
     */
    public function withServerRequest(ServerRequestInterface $serverRequest);
}
