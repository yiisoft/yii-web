<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web;

use Yiisoft\Yii\Web\Session\SessionInterface;

final class Flash implements FlashInterface
{
    private const COUNTERS = '__counters';

    private const FLASH_PARAM = '__flash';

    private ?string $sessionId = null;

    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function get(string $key)
    {
        $flashes = $this->fetch();

        if (!isset($flashes[$key], $flashes[self::COUNTERS][$key])) {
            return null;
        }

        if ($flashes[self::COUNTERS][$key] < 0) {
            // mark for deletion in the next request
            $flashes[self::COUNTERS][$key] = 1;
            $this->save($flashes);
        }

        return $flashes[$key];
    }

    public function getAll(): array
    {
        $flashes = $this->fetch();

        $list = [];
        foreach ($flashes as $key => $value) {
            if ($key === self::COUNTERS) {
                continue;
            }

            $list[$key] = $value;
            if ($flashes[self::COUNTERS][$key] < 0) {
                // mark for deletion in the next request
                $flashes[self::COUNTERS][$key] = 1;
            }
        }

        $this->save($flashes);

        return $list;
    }

    public function set(string $key, $value = true, bool $removeAfterAccess = true): void
    {
        $flashes = $this->fetch();
        $flashes[self::COUNTERS][$key] = $removeAfterAccess ? -1 : 0;
        $flashes[$key] = $value;
        $this->save($flashes);
    }

    public function add(string $key, $value = true, bool $removeAfterAccess = true): void
    {
        $flashes = $this->fetch();
        $flashes[self::COUNTERS][$key] = $removeAfterAccess ? -1 : 0;

        if (empty($flashes[$key])) {
            $flashes[$key] = [$value];
        } elseif (is_array($flashes[$key])) {
            $flashes[$key][] = $value;
        } else {
            $flashes[$key] = [$flashes[$key], $value];
        }

        $this->save($flashes);
    }

    public function remove(string $key): void
    {
        $flashes = $this->fetch();
        unset($flashes[$key], $flashes[self::COUNTERS][$key]);
        $this->save($flashes);
    }

    public function removeAll(): void
    {
        $this->save([self::COUNTERS => []]);
    }

    public function has(string $key): bool
    {
        $flashes = $this->fetch();
        return isset($flashes[$key], $flashes[self::COUNTERS][$key]);
    }


    /**
     * Updates the counters for flash messages and removes outdated flash messages.
     * This method should be called once after session initialization.
     */
    private function updateCounters(): void
    {
        $flashes = $this->session->get(self::FLASH_PARAM, []);
        if (!is_array($flashes)) {
            $flashes = [self::COUNTERS => []];
        }

        $counters = $flashes[self::COUNTERS] ?? [];
        if (!is_array($counters)) {
            $counters = [];
        }


        foreach ($counters as $key => $count) {
            if ($count > 0) {
                unset($counters[$key], $flashes[$key]);
            } elseif ($count === 0) {
                $counters[$key]++;
            }
        }

        $flashes[self::COUNTERS] = $counters;
        $this->save($flashes);
    }

    private function fetch(): array
    {
        // ensure session is active (and has id)
        $this->session->open();
        if ($this->sessionId !== $this->session->getId()) {
            $this->sessionId = $this->session->getId();
            $this->updateCounters();
        }

        return $this->session->get(self::FLASH_PARAM, []);
    }

    private function save(array $flashes): void
    {
        $this->session->set(self::FLASH_PARAM, $flashes);
    }
}
