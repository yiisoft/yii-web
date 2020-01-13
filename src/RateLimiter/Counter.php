<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\SimpleCache\CacheInterface;

/**
 * Counter implements generic сell rate limit algorithm (GCRA) that ensures that after reaching the limit futher
 * requests are distributed equally.
 *
 * @link https://en.wikipedia.org/wiki/Generic_cell_rate_algorithm
 */
final class Counter implements CounterInterface
{
    public const ID_PREFIX = 'rate-limiter-';

    private const MILLISECONDS_PER_SECOND = 1000;

    private int $period;

    private int $limit;

    private float $emissionInterval;

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

        $this->emissionInterval = (float)($this->period / $this->limit);
        $this->storage = $storage;
    }

    public function setId(string $id): void
    {
        $this->id = self::ID_PREFIX . $id;
    }

    public function incrementAndGetResult(): CounterStatistics
    {
        if ($this->id === null) {
            throw new \LogicException('The counter ID should be set');
        }

        $this->arrivalTime = $this->calculateArrivalTime();
        $theoreticalArrivalTime = $this->calculateTheoreticalArrivalTime($this->getStorageValue());
        $remaining = $this->calculateRemaining($theoreticalArrivalTime);
        $resetAfter = $this->calculateResetAfter($theoreticalArrivalTime);

        if ($remaining < 1) {
            $remaining = 0;
        } else {
            $this->setStorageValue($theoreticalArrivalTime);
        }

        return new CounterStatistics($this->limit, $remaining, $resetAfter);
    }

    private function calculateTheoreticalArrivalTime(float $theoreticalArrivalTime): float
    {
        return max($this->arrivalTime, $theoreticalArrivalTime) + $this->emissionInterval;
    }

    private function calculateRemaining(float $theoreticalArrivalTime): int
    {
        $allowAt = $theoreticalArrivalTime - $this->period;

        return (int)((floor($this->arrivalTime - $allowAt) / $this->emissionInterval) + 0.5);
    }

    private function getStorageValue(): float
    {
        return $this->storage->get($this->id, (float)$this->arrivalTime);
    }

    private function setStorageValue(float $theoreticalArrivalTime): void
    {
        $this->storage->set($this->id, $theoreticalArrivalTime);
    }

    private function calculateArrivalTime(): int
    {
        return time() * self::MILLISECONDS_PER_SECOND;
    }

    private function calculateResetAfter(float $theoreticalArrivalTime): int
    {
        return (int)($theoreticalArrivalTime - $this->arrivalTime);
    }
}
