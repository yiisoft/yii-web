<?php
namespace Yiisoft\Yii\Web\User;

use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Yii\Web\Session\Session;
use Yiisoft\Yii\Web\User\Event\AfterLoginEvent;
use Yiisoft\Yii\Web\User\Event\AfterLogoutEvent;
use Yiisoft\Yii\Web\User\Event\BeforeLoginEvent;
use Yiisoft\Yii\Web\User\Event\BeforeLogout;

class User
{
    private const SESSION_AUTH_ID = '__auth_id';
    private const SESSION_AUTH_EXPIRE = '__auth_expire';
    private const SESSION_AUTH_ABSOLUTE_EXPIRE = '__auth_absolute_expire';

    /**
     * @var IdentityRepositoryInterface
     */
    private $identityRepository;

    /**
     * @var AccessCheckerInterface
     */
    private $accessChecker;

    /**
     * @var IdentityInterface
     */
    private $identity;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(IdentityRepositoryInterface $identityRepository, EventDispatcherInterface $eventDispatcher)
    {
        $this->identityRepository = $identityRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setAccessChecker(AccessCheckerInterface $accessChecker): void
    {
        $this->accessChecker = $accessChecker;
    }

    /**
     * Set session to persist authentication status across multiple requests.
     * If not set, authentication has to be performed on each request, which is often the case
     * for stateless application such as RESTful API.
     *
     * @param Session $session
     */
    public function setSession(Session $session): void
    {
        $this->session = $session;
    }

    /**
     * @var int the number of seconds in which the user will be logged out automatically if he
     * remains inactive. If this property is not set, the user will be logged out after
     * the current session expires (c.f. [[Session::timeout]]).
     */
    public $authTimeout;

    /**
     * @var int the number of seconds in which the user will be logged out automatically
     * regardless of activity.
     * Note that this will not work if [[enableAutoLogin]] is `true`.
     */
    public $absoluteAuthTimeout;


    /**
     * @var array MIME types for which this component should redirect to the [[loginUrl]].
     */
    public $acceptableRedirectTypes = ['text/html', 'application/xhtml+xml'];

    /**
     * Returns the identity object associated with the currently logged-in user.
     * When [[enableSession]] is true, this method may attempt to read the user's authentication data
     * stored in session and reconstruct the corresponding identity object, if it has not done so before.
     * @param bool $autoRenew whether to automatically renew authentication status if it has not been done so before.
     * This is only useful when [[enableSession]] is true.
     * @return IdentityInterface the identity object associated with the currently logged-in user.
     * @throws \Throwable
     * @see logout()
     * @see login()
     */
    public function getIdentity($autoRenew = true): IdentityInterface
    {
        if ($this->identity !== null) {
            return $this->identity;
        }
        if ($this->session === null || !$autoRenew) {
            return new GuestIdentity();
        }
        try {
            $this->renewAuthStatus();
        } catch (\Throwable $e) {
            $this->identity = null;
            throw $e;
        }
        return $this->identity;
    }

    /**
     * Sets the user identity object.
     *
     * Note that this method does not deal with session or cookie. You should usually use [[switchIdentity()]]
     * to change the identity of the current user.
     *
     * @param IdentityInterface|null $identity the identity object associated with the currently logged user.
     * Use {{@see GuestIdentity}} to indicate that the current user is a guest.
     */
    public function setIdentity(IdentityInterface $identity): void
    {
        $this->identity = $identity;
    }

    /**
     * Logs in a user.
     *
     * After logging in a user:
     * - the user's identity information is obtainable from the [[identity]] property
     *
     * If [[enableSession]] is `true`:
     * - the identity information will be stored in session and be available in the next requests
     * - in case of `$duration == 0`: as long as the session remains active or till the user closes the browser
     * - in case of `$duration > 0`: as long as the session remains active or as long as the cookie
     *   remains valid by it's `$duration` in seconds when [[enableAutoLogin]] is set `true`.
     *
     * If [[enableSession]] is `false`:
     * - the `$duration` parameter will be ignored
     *
     * @param IdentityInterface $identity the user identity (which should already be authenticated)
     * @param int $duration number of seconds that the user can remain in logged-in status, defaults to `0`
     * @return bool whether the user is logged in
     */
    public function login(IdentityInterface $identity, int $duration = 0): bool
    {
        if ($this->beforeLogin($identity, $duration)) {
            $this->switchIdentity($identity);
            $this->afterLogin($identity, $duration);
        }
        return !$this->isGuest();
    }

    /**
     * Logs in a user by the given access token.
     * This method will first authenticate the user by calling [[IdentityInterface::findIdentityByAccessToken()]]
     * with the provided access token. If successful, it will call [[login()]] to log in the authenticated user.
     * If authentication fails or [[login()]] is unsuccessful, it will return null.
     * @param string $token the access token
     * @param string $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * @return IdentityInterface|null the identity associated with the given access token. Null is returned if
     * the access token is invalid or [[login()]] is unsuccessful.
     */
    public function loginByAccessToken(string $token, string $type = null): ?IdentityInterface
    {
        $identity = $this->identityRepository->findIdentityByToken($token, $type);
        if ($identity && $this->login($identity)) {
            return $identity;
        }
        return null;
    }

    /**
     * Logs out the current user.
     * This will remove authentication-related session data.
     * If `$destroySession` is true, all session data will be removed.
     * @param bool $destroySession whether to destroy the whole session. Defaults to true.
     * This parameter is ignored if [[enableSession]] is false.
     * @return bool whether the user is logged out
     * @throws \Throwable
     */
    public function logout($destroySession = true): bool
    {
        $identity = $this->getIdentity();
        if ($this->isGuest()) {
            return false;
        }
        if ($this->beforeLogout($identity)) {
            $this->switchIdentity(new GuestIdentity());
            if ($destroySession && $this->session) {
                $this->session->destroy();
            }
            $this->afterLogout($identity);
        }
        return $this->isGuest();
    }

    /**
     * Returns a value indicating whether the user is a guest (not authenticated).
     * @return bool whether the current user is a guest.
     * @see getIdentity()
     */
    public function isGuest(): bool
    {
        return $this->getIdentity() instanceof GuestIdentity;
    }

    /**
     * Returns a value that uniquely represents the user.
     * @return string the unique identifier for the user. If `null`, it means the user is a guest.
     * @throws \Throwable
     * @see getIdentity()
     */
    public function getId(): ?string
    {
        return $this->getIdentity()->getId();
    }

    /**
     * This method is called before logging in a user.
     * The default implementation will trigger the [[EVENT_BEFORE_LOGIN]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * @param IdentityInterface $identity the user identity information
     * @param int $duration number of seconds that the user can remain in logged-in status.
     * If 0, it means login till the user closes the browser or the session is manually destroyed.
     * @return bool whether the user should continue to be logged in
     */
    protected function beforeLogin(IdentityInterface $identity, int $duration): bool
    {
        $event = new BeforeLoginEvent($identity, $duration);
        $this->eventDispatcher->dispatch($event);
        return $event->isValid();
    }

    /**
     * This method is called after the user is successfully logged in.
     * The default implementation will trigger the [[EVENT_AFTER_LOGIN]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * @param IdentityInterface $identity the user identity information
     * @param int $duration number of seconds that the user can remain in logged-in status.
     * If 0, it means login till the user closes the browser or the session is manually destroyed.
     */
    protected function afterLogin(IdentityInterface $identity, int $duration): void
    {
        $this->eventDispatcher->dispatch(new AfterLoginEvent($identity, $duration));
    }

    /**
     * This method is invoked when calling [[logout()]] to log out a user.
     * The default implementation will trigger the [[EVENT_BEFORE_LOGOUT]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * @param IdentityInterface $identity the user identity information
     * @return bool whether the user should continue to be logged out
     */
    protected function beforeLogout(IdentityInterface $identity): bool
    {
        $event = new BeforeLogout($identity);
        $this->eventDispatcher->dispatch($event);
        return $event->isValid();
    }

    /**
     * This method is invoked right after a user is logged out via [[logout()]].
     * The default implementation will trigger the [[EVENT_AFTER_LOGOUT]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * @param IdentityInterface $identity the user identity information
     */
    protected function afterLogout(IdentityInterface $identity): void
    {
        $this->eventDispatcher->dispatch(new AfterLogoutEvent($identity));
    }

    /**
     * Switches to a new identity for the current user.
     *
     * When [[enableSession]] is true, this method may use session and/or cookie to store the user identity information,
     * according to the value of `$duration`. Please refer to [[login()]] for more details.
     *
     * This method is mainly called by [[login()]], [[logout()]] and [[loginByCookie()]]
     * when the current user needs to be associated with the corresponding identity information.
     *
     * @param IdentityInterface $identity the identity information to be associated with the current user.
     * In order to indicate that the user is guest, use {{@see GuestIdentity}}.
     */
    public function switchIdentity(IdentityInterface $identity): void
    {
        $this->setIdentity($identity);
        if ($this->session === null) {
            return;
        }

        $this->session->regenerateID();

        $this->session->remove(self::SESSION_AUTH_ID);
        $this->session->remove(self::SESSION_AUTH_EXPIRE);

        if ($identity->getId() === null) {
            return;
        }
        $this->session->set(self::SESSION_AUTH_ID, $identity->getId());
        if ($this->authTimeout !== null) {
            $this->session->set(self::SESSION_AUTH_EXPIRE, time() + $this->authTimeout);
        }
        if ($this->absoluteAuthTimeout !== null) {
            $this->session->set(self::SESSION_AUTH_ABSOLUTE_EXPIRE, time() + $this->absoluteAuthTimeout);
        }
    }

    /**
     * Updates the authentication status using the information from session and cookie.
     *
     * This method will try to determine the user identity using a session variable.
     *
     * If [[authTimeout]] is set, this method will refresh the timer.
     *
     * If the user identity cannot be determined by session, this method will try to [[loginByCookie()|login by cookie]]
     * if [[enableAutoLogin]] is true.
     * @throws \Throwable
     */
    protected function renewAuthStatus(): void
    {
        $id = $this->session->get(self::SESSION_AUTH_ID);

        $identity = null;
        if ($id !== null) {
            $identity = $this->identityRepository->findIdentity($id);
        }
        if ($identity === null) {
            $identity = new GuestIdentity();
        }
        $this->setIdentity($identity);

        if (!($identity instanceof GuestIdentity) && ($this->authTimeout !== null || $this->absoluteAuthTimeout !== null)) {
            $expire = $this->authTimeout !== null ? $this->session->get(self::SESSION_AUTH_ABSOLUTE_EXPIRE) : null;
            $expireAbsolute = $this->absoluteAuthTimeout !== null ? $this->session->get(self::SESSION_AUTH_ABSOLUTE_EXPIRE) : null;
            if (($expire !== null && $expire < time()) || ($expireAbsolute !== null && $expireAbsolute < time())) {
                $this->logout(false);
            } elseif ($this->authTimeout !== null) {
                $this->session->set(self::SESSION_AUTH_EXPIRE, time() + $this->authTimeout);
            }
        }
    }

    /**
     * Checks if the user can perform the operation as specified by the given permission.
     *
     * Note that you must provide access checker via {{@see User::setAccessChecker()}} in order to use this method.
     * Otherwise it will always return false.
     *
     * @param string $permissionName the name of the permission (e.g. "edit post") that needs access check.
     * @param array $params name-value pairs that would be passed to the rules associated
     * with the roles and permissions assigned to the user.
     * @return bool whether the user can perform the operation as specified by the given permission.
     * @throws \Throwable
     */
    public function can(string $permissionName, array $params = []): bool
    {
        if ($this->accessChecker === null) {
            return false;
        }

        return $this->accessChecker->userHasPermission($this->getId(), $permissionName, $params);
    }
}
