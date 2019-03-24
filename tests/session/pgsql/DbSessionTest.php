<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests\session\pgsql;

/**
 * Class DbSessionTest.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 *
 * @group db
 * @group pgsql
 */
class DbSessionTest extends \yii\web\tests\session\AbstractDbSessionTest
{
    protected function setUp()
    {
        parent::setUp();
    }

    protected function getDriverNames()
    {
        return ['pgsql'];
    }
}
