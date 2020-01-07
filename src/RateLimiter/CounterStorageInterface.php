<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

/**
 * Counter value storage
 */
interface CounterStorageInterface
{
    /**
     * @param string $id ID of the counter
     * @return int counter value
     */
    public function get(string $id): int;

    /**
     * @param string $id ID of the counter
     * @param int $value counter value
     * @param int $interval counter value expiration interval in seconds
     */
    public function set(string $id, int $value, int $interval): void;

    /**
     * @param string $id ID of the counter
     * @return bool if storage has value for the counter ID specified
     */
    public function has(string $id): bool;
}
