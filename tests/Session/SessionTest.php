<?php

namespace Yiisoft\Yii\Web\Tests\Session;

use Yiisoft\Yii\Web\Session\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    public function tearDown()
    {
        @session_destroy();
    }

    public function testGetAndSet()
    {
        $session = new Session();
        $session->set('get', 'set');
        self::assertEquals('set', $session->get('get'));
    }

    public function testHas()
    {
        $session = new Session();
        $session->set('has', 'has');
        self::assertTrue($session->has('has'));
    }

    public function testClose()
    {
        $session = new Session();
        $session->set('close', 'close');
        $session->close();
        self::assertEquals(PHP_SESSION_NONE, session_status());
        // because session_destroy() in tearDown doesn't work as expected
        // we need to open session and then destroy it
        $session->open();
        $session->destroy();
    }

    public function testRegenerateID()
    {
        $session = new Session();
        $session->open();
        $id = $session->getId();
        $session->regenerateId();
        self::assertNotEquals($id, $session->getId());
    }

    public function testDiscard()
    {
        $session = new Session();
        $session->set('discard', 'discard');
        $session->discard();
        self::assertEmpty($session->get('discard'));
    }

    public function testGetName()
    {
        $session = new Session();
        self::assertEquals($session->getName(), session_name());
    }

    public function testPull()
    {
        $session = new Session();
        $session->set('pull', 'pull');
        self::assertEquals('pull', $session->pull('pull'));
        self::assertEmpty($session->get('pull'));
    }

    public function testAll()
    {
        $session = new Session();
        $session->set('1', 1);
        $session->set('2', 2);
        self::assertEquals(['1' => 1, '2' => 2], $session->all());
    }

    public function testClear()
    {
        $session = new Session();
        $session->set('1', 1);
        $session->clear();
        self::assertEmpty($session->all());
    }

    public function testSetId()
    {
        $session = new Session();
        $session->setId('sessionId');
        $session->open();
        self::assertEquals(session_id(), $session->getId());
    }

    public function testGetCookieParameters()
    {
        $session = new Session();
        self::assertEquals(session_get_cookie_params(), $session->getCookieParameters());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAlreadyStartedException()
    {
        $session = new Session();
        $session->set('1', 1);
        $session = new Session();
    }
}
