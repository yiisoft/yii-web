<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

/**
 * Stores rate limiter counter value
 */
interface CounterStorageInterface
{
    public function get(string $id): int;

    /**
     * @param string $id
     * @param int $value
     * @param int $interval interval in seconds
     */
    public function set(string $id, int $value, int $interval): void;

    public function has(string $id): bool;
}
