<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Web\Tests\Session\MySql;

/**
 * Class DbSessionTest.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 *
 * @group db
 * @group mysql
 */
class DbSessionTest extends \Yiisoft\Web\Tests\Session\AbstractDbSessionTest
{
    protected function getDriverNames()
    {
        return ['mysql'];
    }
}
