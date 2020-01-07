<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\Http\Message\ServerRequestInterface;

interface CounterInterface
{
    public function init(ServerRequestInterface $request): self;

    public function setIdCallback(callable $callback): self;

    public function setId(string $id): self;

    public function setInterval(int $interval): self;

    public function increment(): void;

    public function getCounterValue(): int;
}
