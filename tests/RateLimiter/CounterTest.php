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
        $this->assertGreaterThanOrEqual(time(), $statistics->getResetTime());
        $this->assertFalse($statistics->isLimitReached());
    }

    /**
     * @test
     */
    public function statisticsShouldBeCorrectWhenLimitIsReached(): void
    {
        $counter = new Counter(2, 4, new ArrayCache());
        $counter->setId('key');

        $statistics = $counter->incrementAndGetResult();
        $this->assertEquals(2, $statistics->getLimit());
        $this->assertEquals(1, $statistics->getRemaining());
        $this->assertGreaterThanOrEqual(time(), $statistics->getResetTime());
        $this->assertFalse($statistics->isLimitReached());

        $statistics = $counter->incrementAndGetResult();
        $this->assertEquals(2, $statistics->getLimit());
        $this->assertEquals(0, $statistics->getRemaining());
        $this->assertGreaterThanOrEqual(time(), $statistics->getResetTime());
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

    /**
     * @test
     */
    public function incrementMustBeUniformAfterLimitIsReached(): void
    {
        $counter = new Counter(10, 1, new ArrayCache());
        $counter->setId('key');

        for ($i = 0; $i < 10; $i++) {
            $counter->incrementAndGetResult();
        }

        for ($i = 0; $i < 5; $i++) {
            usleep(110000); // period(microseconds) / limit + 10ms(cost work)
            $statistics = $counter->incrementAndGetResult();
            $this->assertEquals(1, $statistics->getRemaining());
        }
    }
}
