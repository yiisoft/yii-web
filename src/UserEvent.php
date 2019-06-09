<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Web;

use yii\base\Event;

/**
 * This event class is used for Events triggered by the [[User]] class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class UserEvent extends Event
{
    /**
     * @param string $name event name
     * @param IdentityInterface $identity the action associated with this event.
     */
    public function __construct(string $name, IdentityInterface $identity)
    {
        parent::__construct($name, $identity);
    }

    /**
     * @return IdentityInterface the identity object associated with this event.
     */
    public function getIdentity(): IdentityInterface
    {
        return $this->getTarget();
    }
}
