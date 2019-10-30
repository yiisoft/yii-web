<?php

namespace Yiisoft\Yii\Web\Tests\Session;

use Yiisoft\Yii\Web\Session\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testGetAndSet()
    {
        $session = new Session();
        $session->set('key_get', 'value');
        self::assertEquals('value', $session->get('key_get'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testHas()
    {
        $session = new Session();
        $session->set('key_has', 'value');
        self::assertTrue($session->has('key_has'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testClose()
    {
        $session = new Session();
        $session->set('key_close', 'value');
        $session->close();
        self::assertEquals(PHP_SESSION_NONE, session_status());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegenerateID()
    {
        $session = new Session();
        $session->open();
        $id = $session->getId();
        $session->regenerateId();
        self::assertNotEquals($id, $session->getId());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDiscard()
    {
        $session = new Session();
        $session->set('key_discard', 'value');
        $session->discard();
        self::assertEmpty($session->get('key_discard'));
    }

    public function testGetName()
    {
        $session = new Session();
        self::assertEquals($session->getName(), session_name());
    }

    /**
     * @runInSeparateProcess
     */
    public function testPull()
    {
        $session = new Session();
        $session->set('key_pull', 'value');
        self::assertEquals('value', $session->pull('key_pull'));
        self::assertEmpty($session->get('key_pull'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAll()
    {
        $session = new Session();
        $session->set('key_1', 1);
        $session->set('key_2', 2);
        self::assertEquals(['key_1' => 1, 'key_2' => 2], $session->all());
    }

    /**
     * @runInSeparateProcess
     */
    public function testClear()
    {
        $session = new Session();
        $session->set('key', 'value');
        $session->clear();
        self::assertEmpty($session->all());
    }

    /**
     * @runInSeparateProcess
     */
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
        $session->open();
        new Session();
    }
}
