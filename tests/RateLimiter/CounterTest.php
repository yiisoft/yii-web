<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Http\Method;
use Yiisoft\Yii\Web\RateLimiter\CacheCounterStorage;
use Yiisoft\Yii\Web\RateLimiter\Counter;
use Yiisoft\Yii\Web\RateLimiter\CounterStorageInterface;

final class CounterTest extends TestCase
{
    /**
     * @test
     */
    public function expectedCounterValueInitial(): void
    {
        $storage = $this->getStorage();
        $counter = (new Counter($storage))
            ->init($this->createRequest());

        $this->assertEquals(0, $counter->getCounterValue());
    }

    /**
     * @test
     */
    public function expectedCounterValue(): void
    {
        $storage = $this->getStorage();
        $storage->set('test-id', 30, 10);

        $counter = (new Counter($storage))
            ->withId('test-id')
            ->init($this->createRequest());

        $this->assertEquals(30, $counter->getCounterValue());
    }

    /**
     * @test
     */
    public function init(): void
    {
        $storage = $this->getStorage();
        (new Counter($storage))->init($this->createRequest());

        $this->assertTrue($storage->has('rate-limiter-get-/'));
    }

    /**
     * @test
     */
    public function increment(): void
    {
        $counter = (new Counter($this->getStorage()))
            ->init($this->createRequest());

        for ($i = 0; $i < 1000; $i++) {
            $counter->increment();
        }

        $this->assertEquals(1000, $counter->getCounterValue());
    }

    /**
     * @test
     */
    public function generateIdByCallback(): void
    {
        $storage = $this->getStorage();
        $storage->set('POST', 101, 10);

        $counter = (new Counter($storage))
            ->withIdCallback(
                static function (ServerRequestInterface $request) {
                    return $request->getMethod();
                }
            )
            ->init($this->createRequest(Method::POST));

        $this->assertEquals(101, $counter->getCounterValue());
    }

    /**
     * @test
     */
    public function setInterval(): void
    {
        $counter = (new Counter($this->getStorage()))
            ->withInterval(3)
            ->init($this->createRequest());

        $counter->increment();

        $this->assertEquals(1, $counter->getCounterValue());
        sleep(3);
        $this->assertEquals(0, $counter->getCounterValue());
    }

    /**
     * @test
     */
    public function setCounterId(): void
    {
        $storage = $this->getStorage();
        (new Counter($storage))
            ->withId('test')
            ->init($this->createRequest());

        $this->assertTrue($storage->has('test'));
    }

    /**
     * @test
     */
    public function expectNotInitException(): void
    {
        $this->expectException(\RuntimeException::class);

        (new Counter($this->getStorage()))->increment();
    }

    private function getStorage(): CounterStorageInterface
    {
        return new CacheCounterStorage(new ArrayCache());
    }

    private function createRequest(string $method = Method::GET, string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }
}
