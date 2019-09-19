<?php

namespace Yiisoft\Yii\Web\Auth;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\Web\User\IdentityInterface;

/**
 * AuthInterface is the interface that should be implemented by auth method classes.
 */
interface AuthInterface
{
    /**
     * Authenticates the current user.
     * @param ServerRequestInterface $request
     * @return null|IdentityInterface
     */
    public function authenticate(ServerRequestInterface $request): ?IdentityInterface;

    /**
     * Generates challenges upon authentication failure.
     * For example, some appropriate HTTP headers may be generated.
     * @param $response
     * @return ResponseInterface
     */
    public function challenge(ResponseInterface $response): ResponseInterface;

}
