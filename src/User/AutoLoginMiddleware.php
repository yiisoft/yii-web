<?php

namespace Yiisoft\Yii\Web\User;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Yii\Web\User\User;

/**
 * AutoLoginMiddleware automatically logs user in based on "remember me" cookie
 */
class AutoLoginMiddleware implements MiddlewareInterface
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
        $data = $this->getIdentityAndDurationFromCookie($request);
        if ($this->user->login($data['identity'], $data['duration'])) {
            try {
                $response = $handler->handle($request);
            } catch (\Throwable $e) {
                throw $e;
            }

            return $response;
        } else {
            throw new \Exception("Error authentication");
        }
    }

    /**
     * Determines if an identity cookie has a valid format and contains a valid auth key.
     * This method is used when [[enableAutoLogin]] is true.
     * This method attempts to authenticate a user using the information in the identity cookie.
     * @param ServerRequestInterface $request Request to handle
     * @return array|null Returns an array of 'identity' and 'duration' if valid, otherwise null.
     */
    protected function getIdentityAndDurationFromCookie(ServerRequestInterface $request)
    {
        $cookies = $request->getCookieParams();
        $value = $cookies['remember'] ?? null;

        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);
        if (is_array($data) && count($data) == 3) {
            list($id, $authKey, $duration) = $data;
            $identity = $this->identityRepository->findIdentity($id);
            if ($identity !== null) {
                if (!$identity instanceof IdentityInterface) {
                    throw new \Exception("findIdentity() must return an object implementing IdentityInterface.");
                } else {
                    return ['identity' => $identity, 'duration' => $duration];
                }
            }
        }

        return null;
    }
}
