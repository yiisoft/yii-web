<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\SimpleCache\CacheInterface;

final class RateLimitResult
{
    private int $limit;

    private int $remaining;

    /**
     * @var int milliseconds is left until the rate limit resets
     */
    private int $reset;

    public function __construct(int $limit, int $remaining, int $reset)
    {
        $this->limit = $limit;
        $this->remaining = $remaining;
        $this->reset = $reset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getRemaining(): int
    {
        return $this->remaining;
    }

    public function getReset(): int
    {
        return $this->reset;
    }

    public function remainingIsEmpty(): bool
    {
        return $this->remaining === 0;
    }
}
