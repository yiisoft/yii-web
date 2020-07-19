<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Flash;

use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\Flash;

class FlashTest extends TestCase
{
    /**
     * @var MockArraySessionStorage
     */
    private $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new MockArraySessionStorage([
            '__flash' => [
                '__counters' => [
                    'info' => 0,
                    'error' => 0,
                ],
                'info' => 'Some message to show',
                'error' => 'Some error message to show',
            ],
        ]);
    }

    public function testRemove(): void
    {
        $flash = new Flash($this->session);

        $flash->remove('error');
        $rawFlashes = $this->session->get('__flash');
        $this->assertSame(['__counters' => ['info' => 1], 'info' => 'Some message to show'], $rawFlashes);
    }

    public function testRemoveAll(): void
    {
        $flash = new Flash($this->session);

        $flash->removeAll();

        $rawFlashes = $this->session->get('__flash');
        $this->assertSame(['__counters' => []], $rawFlashes);
    }

    public function testHas(): void
    {
        $flash = new Flash($this->session);

        $this->assertTrue($flash->has('error'));
        $this->assertFalse($flash->has('nope'));

        $rawFlashes = $this->session->get('__flash');
        $this->assertSame([
            '__counters' => [
                'info' => 1,
                'error' => 1,
            ],
            'info' => 'Some message to show',
            'error' => 'Some error message to show',
        ], $rawFlashes);
    }

    public function testGet(): void
    {
        $flash = new Flash($this->session);

        $value = $flash->get('error');
        $this->assertSame('Some error message to show', $value);

        $value = $flash->get('nope');
        $this->assertNull($value);

        $rawFlashes = $this->session->get('__flash');
        $this->assertSame([
            '__counters' => [
                'info' => 1,
                'error' => 1,
            ],
            'info' => 'Some message to show',
            'error' => 'Some error message to show',
        ], $rawFlashes);
    }

    public function testAdd(): void
    {
        $flash = new Flash($this->session);

        $flash->add('info', 'One another message', false);

        $rawFlashes = $this->session->get('__flash');
        $this->assertSame([
            '__counters' => [
                'info' => 0,
                'error' => 1,
            ],
            'info' => [
                'Some message to show',
                'One another message',
            ],
            'error' => 'Some error message to show',
        ], $rawFlashes);
    }

    public function testSet(): void
    {
        $flash = new Flash($this->session);

        $flash->set('warn', 'Warning message');

        $rawFlashes = $this->session->get('__flash');
        $this->assertSame([
            '__counters' => [
                'info' => 1,
                'error' => 1,
                'warn' => -1,
            ],
            'info' => 'Some message to show',
            'error' => 'Some error message to show',
            'warn' => 'Warning message',
        ], $rawFlashes);
    }

    public function testGetAll(): void
    {
        $flash = new Flash($this->session);

        $flashes = $flash->getAll();
        $this->assertSame([
            'info' => 'Some message to show',
            'error' => 'Some error message to show',
        ], $flashes);
    }
}
