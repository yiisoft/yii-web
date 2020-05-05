<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\User;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;

/**
 * AutoLoginMiddleware automatically logs user in based on cookie.
 */
final class AutoLoginMiddleware implements MiddlewareInterface
{
    private User $user;
    private IdentityRepositoryInterface $identityRepository;
    private LoggerInterface $logger;
    private AutoLogin $autoLogin;

    public function __construct(
        User $user,
        IdentityRepositoryInterface $identityRepository,
        LoggerInterface $logger,
        AutoLogin $autoLogin
    ) {
        $this->user = $user;
        $this->identityRepository = $identityRepository;
        $this->logger = $logger;
        $this->autoLogin = $autoLogin;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->authenticateUserFromRequest($request)) {
            $this->logger->warning('Unable to authenticate user by cookie.');
        }

        return $handler->handle($request);
    }

    /**
     * Parse request and try to create identity out of data present.
     *
     * @param ServerRequestInterface $request Request to parse.
     * @return IdentityInterface|null Identity created or null if request data isn't valid.
     */
    private function getIdentityFromRequest(ServerRequestInterface $request): ?IdentityInterface
    {
        try {
            $cookies = $request->getCookieParams();
            $cookieName = $this->autoLogin->getCookieName();
            $data = json_decode($cookies[$cookieName], true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            return null;
        }

        if (!is_array($data) || count($data) !== 2) {
            return null;
        }

        [$id, $authKey] = $data;
        $identity = $this->identityRepository->findIdentity($id);
        if ($identity === null) {
            return null;
        }

        if (!$identity instanceof AutoLoginIdentityInterface) {
            // TODO: throw or write log?
            return null;
        }

        if (!$identity->validateAuthKey($authKey)) {
            $this->logger->warning('Unable to authenticate user by cookie. Invalid auth key.');
            return null;
        }

        return $identity;
    }

    /**
     * Authenticate user if there is data to do so in request.
     *
     * @param ServerRequestInterface $request Request to handle
     * @return bool
     */
    private function authenticateUserFromRequest(ServerRequestInterface $request): bool
    {
        $identity = $this->getIdentityFromRequest($request);

        if ($identity === null) {
            return false;
        }

        return $this->user->login($identity);
    }
}
