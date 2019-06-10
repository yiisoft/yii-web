<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Web\Tests\Session;

use yii\helpers\Yii;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Web\CacheSession;

/**
 * @group web
 */
class CacheSessionTest extends \yii\tests\TestCase
{
    private $_cache;

    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();
        $this->_cache = new Cache(new ArrayCache());
    }

    public function testCacheSession()
    {
        $session = new CacheSession($this->_cache);

        $session->writeSession('test', 'sessionData');
        $this->assertEquals('sessionData', $session->readSession('test'));
        $session->destroySession('test');
        $this->assertEquals('', $session->readSession('test'));
    }

    public function testInvalidCache()
    {
        $this->expectException('TypeError');
        new CacheSession('invalid');
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13537
     */
    public function testNotWrittenSessionDestroying()
    {
        $session = new CacheSession($this->_cache);

        $session->set('foo', 'bar');
        $this->assertEquals('bar', $session->get('foo'));

        $this->assertTrue($session->destroySession($session->getId()));
    }
}
