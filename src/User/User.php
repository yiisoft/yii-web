<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\User;

use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\Web\User\Event\AfterLogin;
use Yiisoft\Yii\Web\User\Event\AfterLogout;
use Yiisoft\Yii\Web\User\Event\BeforeLogin;
use Yiisoft\Yii\Web\User\Event\BeforeLogout;

class User
{
    private const SESSION_AUTH_ID = '__auth_id';
    private const SESSION_AUTH_EXPIRE = '__auth_expire';
    private const SESSION_AUTH_ABSOLUTE_EXPIRE = '__auth_absolute_expire';

    /**
     * @var int|null the number of seconds in which the user will be logged out automatically in case of
     * remaining inactive. If this property is not set, the user will be logged out after
     * the current session expires.
     */
    public ?int $authTimeout = null;

    /**
     * @var int|null the number of seconds in which the user will be logged out automatically
     * regardless of activity.
     */
    public ?int $absoluteAuthTimeout = null;

    private IdentityRepositoryInterface $identityRepository;
    private EventDispatcherInterface $eventDispatcher;

    private ?AccessCheckerInterface $accessChecker = null;
    private ?IdentityInterface $identity = null;
    private ?SessionInterface $session;

    /**
     * @param IdentityRepositoryInterface $identityRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param SessionInterface|null $session session to persist authentication status across multiple requests.
     * If not set, authentication has to be performed on each request, which is often the case for stateless
     * application such as RESTful API.
     */
    public function __construct(
        IdentityRepositoryInterface $identityRepository,
        EventDispatcherInterface $eventDispatcher,
        SessionInterface $session = null
    ) {
        $this->identityRepository = $identityRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->session = $session;
    }

    public function setAccessChecker(AccessCheckerInterface $accessChecker): void
    {
        $this->accessChecker = $accessChecker;
    }

    /**
     * Returns the identity object associated with the currently logged-in user.
     * This method read the user's authentication data
     * stored in session and reconstruct the corresponding identity object, if it has not done so before.
     *
     * @param bool $autoRenew whether to automatically renew authentication status if it has not been done so before.
     *
     * @throws \Throwable
     *
     * @return IdentityInterface the identity object associated with the currently logged-in user.
     *
     * @see logout()
     * @see login()
     */
    public function getIdentity(bool $autoRenew = true): IdentityInterface
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
        return $this->identity ?? new GuestIdentity();
    }

    /**
     * Sets the user identity object.
     *
     * Note that this method does not deal with session. You should usually use {@see switchIdentity()}
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
     * - the user's identity information is obtainable from the {@see getIdentity()}
     * - the identity information will be stored in session and be available in the next requests as long as the session
     *   remains active or till the user closes the browser. Some browsers, such as Chrome, are keeping session when
     *   browser is re-opened.
     *
     * @param IdentityInterface $identity the user identity (which should already be authenticated)
     *
     * @return bool whether the user is logged in
     */
    public function login(IdentityInterface $identity): bool
    {
        if ($this->beforeLogin($identity)) {
            $this->switchIdentity($identity);
            $this->afterLogin($identity);
        }
        return !$this->isGuest();
    }

    /**
     * Logs in a user by the given access token.
     * This method will first authenticate the user by calling {@see IdentityInterface::findIdentityByToken()}
     * with the provided access token. If successful, it will call {@see login()} to log in the authenticated user.
     * If authentication fails or {@see login()} is unsuccessful, it will return null.
     *
     * @param string $token the access token
     * @param string $type the type of the token. The value of this parameter depends on the implementation.
     *
     * @return IdentityInterface|null the identity associated with the given access token. Null is returned if
     * the access token is invalid or {@see login()} is unsuccessful.
     */
    public function loginByAccessToken(string $token, string $type): ?IdentityInterface
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
     *
     * @param bool $destroySession whether to destroy the whole session. Defaults to true.
     *
     * @throws \Throwable
     *
     * @return bool whether the user is logged out
     */
    public function logout(bool $destroySession = true): bool
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
     *
     * @return bool whether the current user is a guest.
     *
     * @see getIdentity()
     */
    public function isGuest(): bool
    {
        return $this->getIdentity() instanceof GuestIdentity;
    }

    /**
     * Returns a value that uniquely represents the user.
     *
     * @throws \Throwable
     *
     * @return string the unique identifier for the user. If `null`, it means the user is a guest.
     *
     * @see getIdentity()
     */
    public function getId(): ?string
    {
        return $this->getIdentity()->getId();
    }

    /**
     * This method is called before logging in a user.
     * The default implementation will trigger the {@see BeforeLogin} event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     *
     * @param IdentityInterface $identity the user identity information
     *
     * @return bool whether the user should continue to be logged in
     */
    private function beforeLogin(IdentityInterface $identity): bool
    {
        $event = new BeforeLogin($identity);
        $this->eventDispatcher->dispatch($event);
        return $event->isValid();
    }

    /**
     * This method is called after the user is successfully logged in.
     *
     * @param IdentityInterface $identity the user identity information
     */
    private function afterLogin(IdentityInterface $identity): void
    {
        $this->eventDispatcher->dispatch(new AfterLogin($identity));
    }

    /**
     * This method is invoked when calling {@see logout()} to log out a user.
     *
     * @param IdentityInterface $identity the user identity information
     *
     * @return bool whether the user should continue to be logged out
     */
    private function beforeLogout(IdentityInterface $identity): bool
    {
        $event = new BeforeLogout($identity);
        $this->eventDispatcher->dispatch($event);
        return $event->isValid();
    }

    /**
     * This method is invoked right after a user is logged out via {@see logout()}.
     *
     * @param IdentityInterface $identity the user identity information
     */
    private function afterLogout(IdentityInterface $identity): void
    {
        $this->eventDispatcher->dispatch(new AfterLogout($identity));
    }

    /**
     * Switches to a new identity for the current user.
     *
     * This method use session to store the user identity information.
     * Please refer to {@see login()} for more details.
     *
     * This method is mainly called by {@see login()} and {@see logout()}
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
     * Updates the authentication status using the information from session.
     *
     * This method will try to determine the user identity using a session variable.
     *
     * If {@see authTimeout} is set, this method will refresh the timer.
     *
     * @throws \Throwable
     */
    private function renewAuthStatus(): void
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
            $expire = $this->authTimeout !== null ? $this->session->get(self::SESSION_AUTH_EXPIRE) : null;
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
     *
     * @throws \Throwable
     *
     * @return bool whether the user can perform the operation as specified by the given permission.
     */
    public function can(string $permissionName, array $params = []): bool
    {
        if ($this->accessChecker === null) {
            return false;
        }

        return $this->accessChecker->userHasPermission($this->getId(), $permissionName, $params);
    }
}
