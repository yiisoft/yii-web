<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

/**
 * Rate limiter counter statistics
 */
final class CounterStatistics
{
    private int $limit;
    private int $remaining;
    private int $reset;

    /**
     * @param int $limit the maximum number of requests allowed with a time period
     * @param int $remaining the number of remaining requests in the current time period
     * @param int $reset seconds to wait until the rate limit resets
     */
    public function __construct(int $limit, int $remaining, int $reset)
    {
        $this->limit = $limit;
        $this->remaining = $remaining;
        $this->reset = $reset;
    }

    /**
     * @return int the maximum number of requests allowed with a time period
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int the number of remaining requests in the current time period
     */
    public function getRemaining(): int
    {
        return $this->remaining;
    }

    /**
     * @return int seconds to wait until the rate limit resets
     */
    public function getReset(): int
    {
        return $this->reset;
    }

    /**
     * @return bool if requests limit is reached
     */
    public function isLimitReached(): bool
    {
        return $this->remaining === 0;
    }
}
