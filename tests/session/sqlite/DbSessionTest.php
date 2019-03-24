<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests\session\sqlite;

use yii\helpers\Yii;

/**
 * Class DbSessionTest.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 *
 * @group db
 * @group sqlite
 */
class DbSessionTest extends \yii\web\tests\session\AbstractDbSessionTest
{
    protected function setUp()
    {
        parent::setUp();

        if (version_compare($this->container->get('db')->getServerVersion(), '3.8.3', '<')) {
            $this->markTestSkipped('SQLite < 3.8.3 does not support "WITH" keyword.');
        }
    }

    protected function getDriverNames()
    {
        return ['sqlite'];
    }
}
