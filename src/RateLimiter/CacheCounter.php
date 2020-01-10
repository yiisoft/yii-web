<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\SimpleCache\CacheInterface;

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
        $emissionInterval = $this->getEmissionInterval();
        $theoreticalArrivalTime = $this->getStorageValue();
        $updatedTheoreticalArrivalTime = $this->calculateTheoreticalArrivalTime(
            $theoreticalArrivalTime,
            $emissionInterval
        );
        $remainingEmpty = $this->remainingEmpty($updatedTheoreticalArrivalTime, $emissionInterval);
        $this->updateStorageValue($remainingEmpty ? $theoreticalArrivalTime : $updatedTheoreticalArrivalTime);

        return $remainingEmpty;
    }

    private function checkParams(): void
    {
        if ($this->id === null) {
            throw new \RuntimeException('The counter id not set');
        }
    }

    private function getEmissionInterval(): float
    {
        return (float)($this->period / $this->limit);
    }

    private function getDelayVariationTolerance(float $emissionInterval)
    {
        return $emissionInterval * $this->limit;
    }

    private function calculateTheoreticalArrivalTime(float $theoreticalArrivalTime, float $emissionInterval): float
    {
        return max($this->arrivalTime, $theoreticalArrivalTime) + $emissionInterval;
    }

    private function remainingEmpty(float $theoreticalArrivalTime, float $emissionInterval): bool
    {
        $allowAt = $theoreticalArrivalTime - $this->getDelayVariationTolerance($emissionInterval);

        return floor((($this->arrivalTime - $allowAt) / $emissionInterval) + 0.5) < 1;
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
