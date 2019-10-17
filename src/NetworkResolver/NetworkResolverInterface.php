<?php


namespace Yiisoft\Yii\Web\NetworkResolver;

use Nyholm\Psr7\Uri;
use Psr\Http\Message\ServerRequestInterface;

interface NetworkResolverInterface
{

    /**
     * @return static
     */
    public function withServerRequest(ServerRequestInterface $serverRequest);

    public function getRemoteIp(): string;

    public function getUserIp(): string;

    /**
     * Relevant security of connection
     */
    public function isSecureConnection(): bool;

    /**
     * Relevant request
     */
    public function getServerRequest(): ServerRequestInterface;
}
