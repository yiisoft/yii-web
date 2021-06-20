<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Mock;

use Psr\EventDispatcher\EventDispatcherInterface;

class MockEventDispatcher implements EventDispatcherInterface
{
    private array $events = [];

    public function dispatch(object $event): void
    {
        $this->events[] = $event;
    }

    public function getClassesEvents(): array
    {
        return array_map(
            static fn ($event) => get_class($event),
            $this->events
        );
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function getFirstEvent(): ?object
    {
        return array_shift($this->events);
    }

    public function getLastEvent(): ?object
    {
        return array_pop($this->events);
    }
}
