<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

/**
 * CounterInterface describes rate limiter counter
 */
interface CounterInterface
{
    /**
     * @param string $id set counter ID
     * Counters with non-equal IDs are counted separately.
     */
    public function setId(string $id): void;

    /**
     * Counts one request as done and returns object containing current counter state
     * @return CounterState
     */
    public function incrementAndGetState(): CounterState;
}
