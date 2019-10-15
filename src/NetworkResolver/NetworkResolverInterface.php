<?php


namespace Yiisoft\Yii\Web\NetworkResolver;

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
     * Relevant request schema
     */
    public function getRequestScheme(): string;

    /**
     * Relevant security of connection
     */
    public function isSecureConnection(): bool;
}
