<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests\session;

use yii\helpers\Yii;
use yii\cache\ArrayCache;
use yii\cache\Cache;
use yii\web\CacheSession;

/**
 * @group web
 */
class CacheSessionTest extends \yii\tests\TestCase
{
    private $cache;

    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();
        $this->cache = new Cache(new ArrayCache());
    }

    public function testCacheSession()
    {
        $session = new CacheSession($this->cache);

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
        $session = new CacheSession($this->cache);

        $session->set('foo', 'bar');
        $this->assertEquals('bar', $session->get('foo'));

        $this->assertTrue($session->destroySession($session->getId()));
    }
}
