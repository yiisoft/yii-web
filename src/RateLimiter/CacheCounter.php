<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\SimpleCache\CacheInterface;

final class CacheCounter implements CounterInterface
{
    private string $id;

    private CacheInterface $storage;

    private CounterQuota $quota;

    private GenericCellRateAlgorithm $algorithm;

    public function __construct(CounterQuota $quota, CacheInterface $storage, GenericCellRateAlgorithm $algorithm)
    {
        $this->quota = $quota;
        $this->storage = $storage;
        $this->algorithm = $algorithm;
    }

    public function limitIsReached(): bool
    {
        $this->checkParams();
        $remaining = $this->algorithm->getRemaining($this->quota, $this->getStorageValue());
        $this->setStorageValue($this->algorithm->getActualTheoreticalArrivalTime());

        return $remaining < 1;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    private function checkParams(): void
    {
        if ($this->id === null) {
            throw new \RuntimeException('The counter id not set');
        }
    }

    private function getStorageValue(): float
    {
        return (float)$this->storage->get($this->id, time());
    }

    private function setStorageValue(float $value): void
    {
        $this->storage->set($this->id, $value);
    }
}
