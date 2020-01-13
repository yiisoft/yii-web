<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Yii\Web\RateLimiter\Counter;

final class CounterTest extends TestCase
{
    /**
     * @test
     */
    public function statisticsShouldBeCorrectWhenLimitIsNotReached(): void
    {
        $counter = new Counter(2, 5, new ArrayCache());
        $counter->setId('key');

        $statistics = $counter->incrementAndGetResult();
        $this->assertEquals(2, $statistics->getLimit());
        $this->assertEquals(1, $statistics->getRemaining());
        $this->assertEquals(2500, $statistics->getReset());
        $this->assertFalse($statistics->isLimitReached());
    }

    /**
     * @test
     */
    public function statisticsShouldBeCorrectWhenLimitIsReached(): void
    {
        $cache = new ArrayCache();
        $cache->set(Counter::ID_PREFIX . 'key', (time() * 1000) + 55000);

        $counter = new Counter(10, 60, $cache);
        $counter->setId('key');

        $statistics = $counter->incrementAndGetResult();
        $this->assertEquals(10, $statistics->getLimit());
        $this->assertEquals(0, $statistics->getRemaining());
        $this->assertEquals(61000, $statistics->getReset());
        $this->assertTrue($statistics->isLimitReached());
    }

    /**
     * @test
     */
    public function shouldNotBeAbleToSetInvalidId(): void
    {
        $this->expectException(\LogicException::class);
        (new Counter(10, 60, new ArrayCache()))->incrementAndGetResult();
    }

    /**
     * @test
     */
    public function shouldNotBeAbleToSetInvalidLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Counter(0, 60, new ArrayCache());
    }

    /**
     * @test
     */
    public function shouldNotBeAbleToSetInvalidPeriod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Counter(10, 0, new ArrayCache());
    }
}
