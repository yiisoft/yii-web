<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

interface StorageInterface
{
    public function getCounterValue(string $id): int;

    /**
     * @param string $id
     * @param int $value
     * @param int $interval in seconds
     */
    public function setCounterValue(string $id, int $value, int $interval): void;

    public function hasCounterValue(string $id): bool;
}
