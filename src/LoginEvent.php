<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Web;

use yii\base\Event;

/**
 * This event is triggered on [[User]] login.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 * @since 3.0
 */
class LoginEvent extends Event
{
    /**
     * @event raised before executing user login.
     * You may set [[Event::isValid]] to `false` to cancel the user login.
     */
    const BEFORE = 'user.login.before';
    /**
     * @event raised after executing user login.
     */
    const AFTER = 'user.login.after';

    /**
     * @var bool whether the login is cookie-based.
     */
    public $cookieBased;
    /**
     * @var int number of seconds that the user can remain in logged-in status.
     * If 0, it means login till the user closes the browser or the session is manually destroyed.
     */
    public $duration;

    /**
     * @param string $name event name
     * @param IdentityInterface $identity the action associated with this event.
     * @param bool $cookieBased whether the login is cookie-based.
     * @param int $duration number of seconds that the user can remain logged-in.
     */
    public function __construct(string $name, IdentityInterface $identity, bool $cookieBased, int $duration)
    {
        parent::__construct($name, $identity);
        $this->cookieBased = $cookieBased;
        $this->duration = $duration;
    }

    /**
     * Creates BEFORE event with result.
     * @param IdentityInterface $identity the user object this event is fired on.
     * @param bool $cookieBased whether the login is cookie-based.
     * @param int $duration number of seconds that the user can remain logged-in.
     * @return self created event
     */
    public static function before(IdentityInterface $identity, bool $cookieBased, int $duration): self
    {
        return new static(static::BEFORE, $identity, $cookieBased, $duration);
    }

    /**
     * Creates AFTER_RUN event with result.
     * @param IdentityInterface $identity the user object this event is fired on.
     * @param bool $cookieBased whether the login is cookie-based.
     * @param int $duration number of seconds that the user can remain logged-in.
     * @return self created event
     */
    public static function after(IdentityInterface $identity, bool $cookieBased, int $duration): self
    {
        return new static(static::AFTER, $identity, $cookieBased, $duration);
    }
}
