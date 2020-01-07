<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\SimpleCache\CacheInterface;

/**
 * Stores counter values in cache
 */
final class CacheCounterStorage implements CounterStorageInterface
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get(string $id): int
    {
        return $this->cache->get($id, 0);
    }

    public function set(string $id, int $value, int $interval): void
    {
        $this->cache->set($id, $value, $interval);
    }

    public function has(string $id): bool
    {
        return $this->cache->has($id);
    }
}
