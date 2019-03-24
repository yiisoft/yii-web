<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use yii\base\Event;

/**
 * This event is triggered on [[User]] logout.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 * @since 3.0
 */
class LogoutEvent extends Event
{
    /**
     * @event raised before executing user logout.
     * You may set [[Event::isValid]] to `false` to cancel the user logout.
     */
    const BEFORE = 'user.logout.before';
    /**
     * @event raised after executing user logout.
     */
    const AFTER = 'user.logout.after';

    /**
     * Creates BEFORE event with result.
     * @param IdentityInterface $identity the user object this event is fired on.
     * @return self created event
     */
    public static function before(IdentityInterface $identity): self
    {
        return new static(static::BEFORE, $identity);
    }

    /**
     * Creates AFTER_RUN event with result.
     * @param IdentityInterface $identity the user object this event is fired on.
     * @return self created event
     */
    public static function after(IdentityInterface $identity): self
    {
        return new static(static::AFTER, $identity);
    }
}
