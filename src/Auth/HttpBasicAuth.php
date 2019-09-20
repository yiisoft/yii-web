<?php
namespace Yiisoft\Yii\Web\Auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\Web\User\IdentityInterface;
use Yiisoft\Yii\Web\User\IdentityRepositoryInterface;

/**
 * HttpBasicAuth is an action filter that supports the HTTP Basic authentication method.
 *
 * > Tip: In case authentication does not work like expected, make sure your web server passes
 * username and password to `$_SERVER['PHP_AUTH_USER']` and `$_SERVER['PHP_AUTH_PW']` variables.
 * If you are using Apache with PHP-CGI, you might need to add this line to your `.htaccess` file:
 * ```
 * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]
 * ```
 */
final class HttpBasicAuth implements AuthInterface
{
    /**
     * @var string the HTTP authentication realm
     */
    public $realm = 'api';
    /**
     * @var callable a PHP callable that will authenticate the user with the HTTP basic auth information.
     * The callable receives a username and a password as its parameters. It should return an identity object
     * that matches the username and password. Null should be returned if there is no such identity.
     * The callable will be called only if current user is not authenticated.
     *
     * The following code is a typical implementation of this callable:
     *
     * ```php
     * function ($username, $password) {
     *     return \app\models\User::findOne([
     *         'username' => $username,
     *         'password' => $password,
     *     ]);
     * }
     * ```
     *
     * If this property is not set, the username information will be considered as an access token
     * while the password information will be ignored. The [[Yiisoft\Yii\Web\User\IdentityRepositoryInterface::findIdentityByToken()]]
     * method will be called to authenticate and login the user.
     */
    public $auth;
    /**
     * @var IdentityRepositoryInterface
     */
    private $identityRepository;

    public function __construct(IdentityRepositoryInterface $identityRepository)
    {
        $this->identityRepository = $identityRepository;
    }

    public function authenticate(ServerRequestInterface $request): ?IdentityInterface
    {
        [$username, $password] = $this->getAuthCredentials($request);

        if ($this->auth) {
            if ($username !== null || $password !== null) {
                $identity = \call_user_func($this->auth, $username, $password);

                return $identity;
            }
        } elseif ($username !== null) {
            $identity = $this->identityRepository->findIdentityByToken($username, get_class($this));

            return $identity;
        }

        return null;
    }

    public function challenge(ResponseInterface $response): ResponseInterface
    {
        return $response->withHeader('WWW-Authenticate', "Basic realm=\"{$this->realm}\"");
    }

    private function getAuthCredentials(ServerRequestInterface $request): array
    {
        $username = $_SERVER['PHP_AUTH_USER'] ?? null;
        $password = $_SERVER['PHP_AUTH_PW'] ?? null;
        if ($username !== null || $password !== null) {
            return [$username, $password];
        }

        /*
         * Apache with php-cgi does not pass HTTP Basic authentication to PHP by default.
         * To make it work, add the following line to to your .htaccess file:
         *
         * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
         */
        $headers = $request->getHeader('Authorization');
        $authToken = !empty($headers) ? \reset($headers) : $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        if ($authToken !== null && strncasecmp($authToken, 'basic', 5) === 0) {
            $parts = array_map(static function ($value) {
                return strlen($value) === 0 ? null : $value;
            }, explode(':', base64_decode(mb_substr($authToken, 6)), 2));

            if (\count($parts) < 2) {
                return [$parts[0], null];
            }

            return $parts;
        }

        return [null, null];
    }
}
