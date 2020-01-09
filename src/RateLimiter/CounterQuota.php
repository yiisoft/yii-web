<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

/**
 * CounterQuota data transfer counter settings
 */
final class CounterQuota
{
    private int $limit;

    private int $period;

    public function __construct(int $limit, int $period)
    {
        $this->limit = $limit;
        $this->period = $period;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }
}
