<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

final class GenericCellRateAlgorithm
{
    private int $arrivalTime;

    private CounterQuota $quota;

    private float $actualTheoreticalArrivalTime;

    public function getRemaining(CounterQuota $quota, float $theoreticalArrivalTime): float
    {
        $this->quota = $quota;
        $this->arrivalTime = time();
        $emissionInterval = $this->getEmissionInterval();
        $updatedTheoreticalArrivalTime = $this->calculateTheoreticalArrivalTime(
            $theoreticalArrivalTime,
            $emissionInterval
        );
        $remaining = $this->calculateRemaining($updatedTheoreticalArrivalTime, $emissionInterval);

        $this->actualTheoreticalArrivalTime = $remaining < 1 ? $theoreticalArrivalTime : $updatedTheoreticalArrivalTime;

        return $remaining;
    }

    public function getActualTheoreticalArrivalTime(): ?float
    {
        return $this->actualTheoreticalArrivalTime;
    }

    private function getEmissionInterval(): float
    {
        return (float)($this->quota->getPeriod() / $this->getLimit());
    }

    private function getDelayVariationTolerance(float $emissionInterval)
    {
        return $emissionInterval * $this->getLimit();
    }

    private function calculateTheoreticalArrivalTime(float $theoreticalArrivalTime, float $emissionInterval): float
    {
        return max($this->arrivalTime, $theoreticalArrivalTime) + $emissionInterval;
    }

    private function calculateRemaining(float $theoreticalArrivalTime, float $emissionInterval): float
    {
        $allowAt = $theoreticalArrivalTime - $this->getDelayVariationTolerance($emissionInterval);

        return floor((($this->arrivalTime - $allowAt) / $emissionInterval) + 0.5);
    }

    private function getLimit(): int
    {
        return $this->quota->getLimit() + 1;
    }
}
