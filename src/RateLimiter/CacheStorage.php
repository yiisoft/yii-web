<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\SimpleCache\CacheInterface;

final class CacheStorage implements StorageInterface
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getCounterValue(string $id): int
    {
        return $this->cache->get($id, 0);
    }

    public function setCounterValue(string $id, int $value, int $interval): void
    {
        $this->cache->set($id, $value, $interval);
    }

    public function hasCounterValue(string $id): bool
    {
        return $this->cache->has($id);
    }
}
