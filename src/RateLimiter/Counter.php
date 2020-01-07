<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\RateLimiter;

use Psr\Http\Message\ServerRequestInterface;

final class Counter
{
    private int $interval = 360;

    private ?string $id = null;

    /**
     * @var callable
     */
    private $idCallback;

    private bool $init = false;

    private StorageInterface $storage;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function init(ServerRequestInterface $request): self
    {
        $this->id = $this->generateId($request);

        if (!$this->storage->hasCounterValue($this->id)) {
            $this->storage->setCounterValue($this->id, 0, $this->interval);
        }

        $this->init = true;

        return $this;
    }

    public function setIdCallback(callable $callback): self
    {
        $this->idCallback = $callback;

        return $this;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setInterval(int $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    public function increment(): void
    {
        $value = $this->getCounterValue();
        $value++;

        $this->storage->setCounterValue($this->id, $value, $this->interval);
    }

    public function getCounterValue(): int
    {
        $this->checkInit();
        return $this->storage->getCounterValue($this->id);
    }

    private function hasIdCallback(): bool
    {
        return $this->idCallback !== null;
    }

    private function generateId(ServerRequestInterface $request): string
    {
        if ($this->hasIdCallback()) {
            return \call_user_func($this->idCallback, $request);
        }

        return $this->id ?? $this->generateIdFromRequest($request);
    }

    private function generateIdFromRequest(ServerRequestInterface $request): string
    {
        return strtolower('rate-limiter-' . $request->getMethod() . '-' . $request->getUri()->getPath());
    }

    private function checkInit(): void
    {
        if (!$this->init) {
            throw new \RuntimeException('The counter was not initiated');
        }
    }
}
