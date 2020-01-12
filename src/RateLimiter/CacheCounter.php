<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\SimpleCache\CacheInterface;

/**
 * CacheCounter implements generic сell rate limit algorithm https://en.wikipedia.org/wiki/Generic_cell_rate_algorithm
 */
final class CacheCounter
{
    public const ID_PREFIX = 'rate-limiter-';

    private const MILLISECONDS_PER_SECOND = 1000;

    private int $period;

    private int $limit;

    private ?string $id = null;

    private CacheInterface $storage;

    private int $arrivalTime;

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
    }

    public function setId(string $id): void
    {
        $this->id = self::ID_PREFIX . $id;
    }

    public function incrementAndGetResult(): RateLimitResult
    {
        if ($this->id === null) {
            throw new \LogicException('The counter id not set');
        }

        $this->arrivalTime = $this->getArrivalTime();
        $theoreticalArrivalTime = $this->calculateTheoreticalArrivalTime($this->getStorageValue());
        $remaining = $this->getRemaining($theoreticalArrivalTime);

        if ($remaining < 1) {
            return new RateLimitResult($this->limit, 0, $this->getResetAfter($theoreticalArrivalTime));
        }

        $this->setStorageValue($theoreticalArrivalTime);

        return new RateLimitResult($this->limit, (int)$remaining, $this->getResetAfter($theoreticalArrivalTime));
    }

    private function getEmissionInterval(): float
    {
        return (float)($this->period / $this->limit);
    }

    private function calculateTheoreticalArrivalTime(float $theoreticalArrivalTime): float
    {
        return max($this->arrivalTime, $theoreticalArrivalTime) + $this->getEmissionInterval();
    }

    private function getRemaining(float $theoreticalArrivalTime): float
    {
        $allowAt = $theoreticalArrivalTime - $this->period;

        return (floor($this->arrivalTime - $allowAt) / $this->getEmissionInterval()) + 0.5;
    }

    private function getStorageValue(): float
    {
        return $this->storage->get($this->id, (float)$this->arrivalTime);
    }

    private function setStorageValue(float $theoreticalArrivalTime): void
    {
        $this->storage->set($this->id, $theoreticalArrivalTime);
    }

    private function getArrivalTime(): int
    {
        return time() * self::MILLISECONDS_PER_SECOND;
    }

    private function getResetAfter(float $theoreticalArrivalTime): int
    {
        return (int)($theoreticalArrivalTime - $this->arrivalTime);
    }
}
