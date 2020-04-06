<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\User;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Yii\Web\User\User;

/**
 * AutoLoginMiddleware automatically logs user in based on "remember me" cookie
 */
final class AutoLoginMiddleware implements MiddlewareInterface
{
    private User $user;
    private IdentityRepositoryInterface $identityRepository;

    public function __construct(
        User $user,
        IdentityRepositoryInterface $identityRepository
    ) {
        $this->user = $user;
        $this->identityRepository = $identityRepository;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->authenticateUserFromRequest($request)) {
            throw new \Exception('Error authentication');
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

        [$id, , $duration] = $data;
        $identity = $this->identityRepository->findIdentity($id);
        if ($identity === null) {
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
