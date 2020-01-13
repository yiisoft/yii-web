<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

interface CounterInterface
{
    public function setId(string $id): void;

    public function incrementAndGetResult(): RateLimitResult;
}
