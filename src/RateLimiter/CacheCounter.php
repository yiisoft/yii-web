<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\SimpleCache\CacheInterface;

/**
 * CacheCounter implements generic сell rate limit algorithm https://en.wikipedia.org/wiki/Generic_cell_rate_algorithm
 */
final class CacheCounter
{
    private int $period;

    private int $limit;

    private ?string $id = null;

    private CacheInterface $storage;

    private int $arrivalTime;

    public function __construct(int $limit, int $period, CacheInterface $storage)
    {
        $this->limit = $limit;
        $this->period = $period;
        $this->storage = $storage;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function limitIsReached(): bool
    {
        $this->checkParams();
        $this->arrivalTime = time();
        $theoreticalArrivalTime = $this->getStorageValue();
        $updatedTheoreticalArrivalTime = $this->calculateTheoreticalArrivalTime($theoreticalArrivalTime);
        $remainingEmpty = $this->remainingEmpty($updatedTheoreticalArrivalTime);
        $this->updateStorageValue($remainingEmpty ? $theoreticalArrivalTime : $updatedTheoreticalArrivalTime);

        return $remainingEmpty;
    }

    private function checkParams(): void
    {
        if ($this->id === null) {
            throw new \RuntimeException('The counter id not set');
        }

        if ($this->limit < 1) {
            throw new \InvalidArgumentException('The limit must be a positive value.');
        }

        if ($this->period < 1) {
            throw new \InvalidArgumentException('The period must be a positive value.');
        }
    }

    private function getEmissionInterval(): float
    {
        return (float)($this->period / $this->limit);
    }

    private function calculateTheoreticalArrivalTime(float $theoreticalArrivalTime): float
    {
        return max($this->arrivalTime, $theoreticalArrivalTime) + $this->getEmissionInterval();
    }

    private function remainingEmpty(float $theoreticalArrivalTime): bool
    {
        $allowAt = $theoreticalArrivalTime - $this->period;

        return ((floor($this->arrivalTime - $allowAt) / $this->getEmissionInterval()) + 0.5) < 1;
    }

    private function getStorageValue(): float
    {
        return $this->storage->get($this->id, (float)$this->arrivalTime);
    }

    private function updateStorageValue(float $theoreticalArrivalTime): void
    {
        $this->storage->set($this->id, $theoreticalArrivalTime);
    }
}
