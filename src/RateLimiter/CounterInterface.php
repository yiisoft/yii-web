<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

interface CounterInterface
{
    public function limitIsReached(): bool;

    public function setId(string $id): void;
}
