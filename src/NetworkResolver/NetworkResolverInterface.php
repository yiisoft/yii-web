<?php


namespace Yiisoft\Yii\Web\NetworkResolver;

use Psr\Http\Message\ServerRequestInterface;

interface NetworkResolverInterface
{
    public function getRemoteIp(ServerRequestInterface $serverRequest): string;

    public function getUserIp(ServerRequestInterface $serverRequest): string;

    /**
     * The schema for the request from the user
     */
    public function getRequestScheme(ServerRequestInterface $serverRequest): string;

    /**
     * The connection is secure from the user
     */
    public function isSecureConnection(ServerRequestInterface $serverRequest): bool;
}
