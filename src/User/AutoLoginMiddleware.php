<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\User;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;

/**
 * AutoLoginMiddleware automatically logs user in based on "remember me" cookie
 */
final class AutoLoginMiddleware implements MiddlewareInterface
{
    private User $user;
    private IdentityRepositoryInterface $identityRepository;
    private LoggerInterface $logger;

    public function __construct(
        User $user,
        IdentityRepositoryInterface $identityRepository,
        LoggerInterface $logger
    ) {
        $this->user = $user;
        $this->identityRepository = $identityRepository;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->authenticateUserFromRequest($request)) {
            $this->logger->warning('Unable to authenticate user by cookie.');
        }

        return $handler->handle($request);
    }

    /**
     * Parse and determines if an identity cookie has a valid format.
     * @param ServerRequestInterface $request Request to handle
     * @return array Returns an array of 'identity' and 'duration' if valid, otherwise [].
     */
    private function parseCredentials(ServerRequestInterface $request): array
    {
        try {
            $cookies = $request->getCookieParams();
            $data = json_decode($cookies['remember'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            return [];
        }

        if (!is_array($data) || count($data) !== 3) {
            return [];
        }

        [$id, $authKey, $duration] = $data;
        $identity = $this->identityRepository->findIdentity($id);
        if ($identity === null) {
            return [];
        }

        if (!$this->user->validateAuthKey($authKey)) {
            $this->logger->warning('Unable to authenticate user by cookie. Invalid auth key.');
            return [];
        }

        return ['identity' => $identity, 'duration' => $duration];
    }

    /**
     * Check if the user can authenticate and if everything is ok, authenticate
     * @param ServerRequestInterface $request Request to handle
     * @return bool
     */
    private function authenticateUserFromRequest(ServerRequestInterface $request): bool
    {
        $data = $this->parseCredentials($request);

        if ($data === []) {
            return false;
        }

        return $this->user->login($data['identity'], $data['duration']);
    }
}
