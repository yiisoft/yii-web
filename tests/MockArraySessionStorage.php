<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests;

use Yiisoft\Session\SessionInterface;

class MockArraySessionStorage implements SessionInterface
{
    private $id = '';

    private $name = '';

    private $started = false;

    private $closed = false;

    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function close(): void
    {
        $this->closed = true;
        $this->id = null;
    }

    public function open(): void
    {
        if ($this->isActive()) {
            return;
        }

        if (empty($this->id)) {
            $this->id = $this->generateId();
        }

        $this->started = true;
        $this->closed = false;
    }

    public function isActive(): bool
    {
        return $this->started && !$this->closed;
    }

    public function regenerateId(): void
    {
        $this->id = $this->generateId();
    }

    public function discard(): void
    {
        $this->close();
    }

    public function all(): array
    {
        return $this->data;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function pull(string $key)
    {
        $value = $this->data[$key] ?? null;
        $this->remove($key);
        return $value;
    }

    public function destroy(): void
    {
        $this->close();
    }

    public function getCookieParameters(): array
    {
        return [];
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $sessionId): void
    {
        $this->id = $sessionId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function clear(): void
    {
        $this->data = [];
    }

    private function generateId(): string
    {
        return hash('sha256', uniqid('ss_mock_', true));
    }
}
