<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\tests\web\session\pgsql;

/**
 * Class DbSessionTest.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 *
 * @group db
 * @group pgsql
 */
class DbSessionTest extends \yii\tests\web\session\AbstractDbSessionTest
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
