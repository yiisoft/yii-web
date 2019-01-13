<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests\filters\stubs;

use yii\base\Component;
use yii\exceptions\NotSupportedException;
use yii\web\IdentityInterface;

/**
 * Class UserIdentity.
 * @author Dmitry Naumenko <d.naumenko.a@gmail.com>
 * @since 2.0.7
 */
class UserIdentity extends Component implements IdentityInterface
{
    private static $ids = [
        'user1',
        'user2',
        'user3',
    ];

    private static $tokens = [
        'token1' => 'user1',
        'token2' => 'user2',
        'token3' => 'user3',
    ];

    private $_id;

    private $_token;

    public static function findIdentity($id): ?IdentityInterface
    {
        if (\in_array($id, static::$ids, true)) {
            $identitiy = new static();
            $identitiy->_id = $id;
            return $identitiy;
        }
    }

    public static function findIdentityByAccessToken($token, $type = null): ?IdentityInterface
    {
        if (isset(static::$tokens[$token])) {
            $id = static::$tokens[$token];
            $identitiy = new static();
            $identitiy->_id = $id;
            $identitiy->_token = $token;
            return $identitiy;
        }
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getAuthKey(): string
    {
        throw new NotSupportedException();
    }

    public function validateAuthKey(string $authKey): bool
    {
        throw new NotSupportedException();
    }
}
