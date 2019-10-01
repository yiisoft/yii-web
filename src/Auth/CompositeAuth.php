<?php
namespace Yiisoft\Yii\Web\Auth;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\Web\User\IdentityInterface;

/**
 * CompositeAuth is an action filter that supports multiple authentication methods at the same time.
 *
 * The authentication methods contained by CompositeAuth are configured via [[authMethods]],
 * which is a list of supported authentication class configurations.
 */
final class CompositeAuth implements AuthInterface
{
    /**
     * @var AuthInterface[]
     */
    private $authMethods = [];
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function authenticate(ServerRequestInterface $request): ?IdentityInterface
    {
        foreach ($this->authMethods as $i => $auth) {
            if (!$auth instanceof AuthInterface) {
                $this->authMethods[$i] = $auth = $this->container->get($auth);
                if (!$auth instanceof AuthInterface) {
                    throw new \RuntimeException(get_class($auth) . ' must implement Yiisoft\Yii\Web\Auth\AuthInterface');
                }
            }

            $identity = $auth->authenticate($request);
            if ($identity !== null) {
                return $identity;
            }
        }

        return null;
    }

    public function challenge(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->authMethods as $method) {
            $response = $method->challenge($response);
        }
        return $response;
    }

    public function setAuthMethods(array $methods): void
    {
        $this->authMethods = $methods;
    }
}
