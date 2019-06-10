<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Web\Tests\Session\PgSql;

/**
 * Class DbSessionTest.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 *
 * @group db
 * @group pgsql
 */
class DbSessionTest extends \Yiisoft\Web\Tests\Session\AbstractDbSessionTest
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
