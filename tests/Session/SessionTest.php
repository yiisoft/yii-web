<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Session;

use Yiisoft\Yii\Web\Session\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    public function testGetAndSet(): void
    {
        $session = new Session();
        $session->set('key_get', 'value');
        self::assertEquals('value', $session->get('key_get'));
        $session->destroy();
    }

    public function testHas(): void
    {
        $session = new Session();
        $session->set('key_has', 'value');
        self::assertTrue($session->has('key_has'));
        $session->destroy();
    }

    public function testClose(): void
    {
        $session = new Session();
        $session->set('key_close', 'value');
        $session->close();
        self::assertEquals(PHP_SESSION_NONE, session_status());

        $session->open();
        $session->destroy();
    }

    public function testRegenerateID(): void
    {
        $session = new Session();
        $session->open();
        $id = $session->getId();
        $session->regenerateId();
        self::assertNotEquals($id, $session->getId());
        $session->destroy();
    }

    public function testDiscard(): void
    {
        $session = new Session();
        $session->set('key_discard', 'value');
        $session->discard();
        self::assertEmpty($session->get('key_discard'));
        $session->destroy();
    }

    public function testGetName(): void
    {
        $session = new Session();
        self::assertEquals($session->getName(), session_name());
        $session->destroy();
    }

    public function testPull(): void
    {
        $session = new Session();
        $session->set('key_pull', 'value');
        self::assertEquals('value', $session->pull('key_pull'));
        self::assertEmpty($session->get('key_pull'));
        $session->destroy();
    }

    public function testAll(): void
    {
        $session = new Session();
        $session->set('key_1', 1);
        $session->set('key_2', 2);
        self::assertEquals(['key_1' => 1, 'key_2' => 2], $session->all());
        $session->destroy();
    }

    public function testClear(): void
    {
        $session = new Session();
        $session->set('key', 'value');
        $session->clear();
        self::assertEmpty($session->all());
        $session->destroy();
    }

    public function testSetId(): void
    {
        $session = new Session();
        $session->setId('sessionId');
        $session->open();
        self::assertEquals(session_id(), $session->getId());
        $session->destroy();
    }

    public function testGetCookieParameters(): void
    {
        $session = new Session();
        self::assertEquals(session_get_cookie_params(), $session->getCookieParameters());
    }
}
