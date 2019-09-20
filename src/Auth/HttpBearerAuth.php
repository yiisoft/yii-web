<?php
namespace Yiisoft\Yii\Web\Auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\Web\User\IdentityInterface;
use Yiisoft\Yii\Web\User\IdentityRepositoryInterface;

/**
 * HttpHeaderAuth is an action filter that supports HTTP authentication through HTTP Headers.
 *
 * The default implementation of HttpHeaderAuth uses the [[Yiisoft\Yii\Web\User\IdentityRepositoryInterface::findIdentityByToken()|findIdentityByToken()]]
 * method of the `user` application component and passes the value of the `X-Api-Key` header. This implementation is used
 * for authenticating API clients.
 */
final class HttpBearerAuth implements AuthInterface
{
    use HttpHeaderAuthTrait;
    private const HEADER_NAME = 'Authorization';
    private const PATTERN = '/^Bearer\s+(.*?)$/';

    /**
     * @var string the HTTP authentication realm
     */
    private $realm = 'api';
    /**
     * @var IdentityRepositoryInterface
     */
    private $identityRepository;

    public function __construct(IdentityRepositoryInterface $identityRepository)
    {
        $this->identityRepository = $identityRepository;
        $this->header = self::HEADER_NAME;
        $this->pattern = self::PATTERN;
    }

    public function authenticate(ServerRequestInterface $request): ?IdentityInterface
    {
        $authToken = $this->getAuthToken($request);
        if ($authToken !== null) {

            return $this->identityRepository->findIdentityByToken($authToken, get_class($this));
        }

        return null;
    }

    public function challenge(ResponseInterface $response): ResponseInterface
    {
        return $response->withHeader('WWW-Authenticate', "{$this->header} realm=\"{$this->realm}\"");
    }

    public function setRealm(string $realm): void
    {
        $this->realm = $realm;
    }
}
