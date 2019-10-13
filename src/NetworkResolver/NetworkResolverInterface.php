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
     * The schema for the request from the user
     */
    public function getRequestScheme(): string;

    /**
     * The connection is secure from the user
     */
    public function isSecureConnection(): bool;
}
