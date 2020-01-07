<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Http\Method;
use Yiisoft\Yii\Web\RateLimiter\CacheStorage;
use Yiisoft\Yii\Web\RateLimiter\Counter;
use Yiisoft\Yii\Web\RateLimiter\StorageInterface;

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
        $storage->setCounterValue('test-id', 30, 10);

        $counter = (new Counter($storage))
            ->setId('test-id')
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

        $this->assertTrue($storage->hasCounterValue('rate-limiter-get-/'));
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
        $storage->setCounterValue('POST', 101, 10);

        $counter = (new Counter($storage))
            ->setIdCallback(
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
            ->setInterval(3)
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
            ->setId('test')
            ->init($this->createRequest());

        $this->assertTrue($storage->hasCounterValue('test'));
    }

    /**
     * @test
     */
    public function expectNotInitException(): void
    {
        $this->expectException(\RuntimeException::class);

        (new Counter($this->getStorage()))->increment();
    }

    private function getStorage(): StorageInterface
    {
        return new CacheStorage(new ArrayCache());
    }

    private function createRequest(string $method = Method::GET, string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }
}
