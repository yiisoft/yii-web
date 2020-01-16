<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\SimpleCache\CacheInterface;

/**
 * Counter implements generic сell rate limit algorithm (GCRA) that ensures that after reaching the limit futher
 * increments are distributed equally.
 *
 * @link https://en.wikipedia.org/wiki/Generic_cell_rate_algorithm
 */
final class Counter implements CounterInterface
{
    private const ID_PREFIX = 'rate-limiter-';

    private const MILLISECONDS_PER_SECOND = 1000;

    /**
     * @var int period to apply limit to, in milliseconds
     */
    private int $period;

    private int $limit;

    /**
     * @var float maximum interval before next increment, in milliseconds
     * In GCRA it is known as emission interval.
     */
    private float $incrementInterval;

    private ?string $id = null;

    private CacheInterface $storage;

    /**
     * @var int last increment time
     * In GCRA it's known as arrival time
     */
    private int $lastIncrementTime;

    /**
     * @param int $limit maximum number of increments that could be performed before increments are limited
     * @param int $period period to apply limit to, in seconds
     * @param CacheInterface $storage
     */
    public function __construct(int $limit, int $period, CacheInterface $storage)
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException('The limit must be a positive value.');
        }

        if ($period < 1) {
            throw new \InvalidArgumentException('The period must be a positive value.');
        }

        $this->limit = $limit;
        $this->period = $period * self::MILLISECONDS_PER_SECOND;
        $this->storage = $storage;

        $this->incrementInterval = (float)($this->period / $this->limit);
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getCacheKey(): string
    {
        return self::ID_PREFIX . $this->id;
    }

    public function incrementAndGetState(): CounterState
    {
        if ($this->id === null) {
            throw new \LogicException('The counter ID should be set');
        }

        $this->lastIncrementTime = $this->getCurrentTime();
        $theoreticalNextIncrementTime = $this->calculateTheoreticalNextIncrementTime(
            $this->getLastStoredTheoreticalNextIncrementTime()
        );
        $remaining = $this->calculateRemaining($theoreticalNextIncrementTime);
        $resetAfter = $this->calculateResetAfter($theoreticalNextIncrementTime);

        if ($remaining >= 1) {
            $this->storeTheoreticalNextIncrementTime($theoreticalNextIncrementTime);
        }

        return new CounterState($this->limit, $remaining, $resetAfter);
    }

    /**
     * @param float $storedTheoreticalNextIncrementTime
     * @return float theoretical increment time that would be expected from equally spaced increments at exactly rate limit
     * In GCRA it is known as TAT, theoretical arrival time.
     */
    private function calculateTheoreticalNextIncrementTime(float $storedTheoreticalNextIncrementTime): float
    {
        return max($this->lastIncrementTime, $storedTheoreticalNextIncrementTime) + $this->incrementInterval;
    }

    /**
     * @param float $theoreticalNextIncrementTime
     * @return int the number of remaining requests in the current time period
     */
    private function calculateRemaining(float $theoreticalNextIncrementTime): int
    {
        $incrementAllowedAt = $theoreticalNextIncrementTime - $this->period;

        return (int)(round($this->lastIncrementTime - $incrementAllowedAt) / $this->incrementInterval);
    }

    private function getLastStoredTheoreticalNextIncrementTime(): float
    {
        return $this->storage->get($this->getCacheKey(), (float)$this->lastIncrementTime);
    }

    private function storeTheoreticalNextIncrementTime(float $theoreticalNextIncrementTime): void
    {
        $this->storage->set($this->getCacheKey(), $theoreticalNextIncrementTime);
    }

    /**
     * @param float $theoreticalNextIncrementTime
     * @return int timestamp to wait until the rate limit resets
     */
    private function calculateResetAfter(float $theoreticalNextIncrementTime): int
    {
        return (int)($theoreticalNextIncrementTime / self::MILLISECONDS_PER_SECOND);
    }

    private function getCurrentTime(): int
    {
        return (int)round(microtime(true) * self::MILLISECONDS_PER_SECOND);
    }
}
