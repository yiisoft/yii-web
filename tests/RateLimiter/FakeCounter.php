<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

use Yiisoft\Yii\Web\RateLimiter\CounterInterface;
use Yiisoft\Yii\Web\RateLimiter\CounterState;

final class FakeCounter implements CounterInterface
{
    private int $remaining;
    private int $limit;
    private int $reset;
    private string $id;

    public function __construct(int $limit, int $reset)
    {
        $this->reset = $reset;
        $this->limit = $limit;
        $this->remaining = $limit;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function incrementAndGetState(): CounterState
    {
        $this->remaining--;
        return new CounterState($this->limit, $this->remaining, $this->reset);
    }
}
