<?php

namespace Yiisoft\Yii\Web\Tests\Config;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Yii\Web\Config\EventConfigurator;

class EventConfiguratorTest extends TestCase
{
    public function testAddEventListeners(): void
    {
        $event = new Event();

        $container = $this->getContainer([Event::class => new Event()]);
        $provider = new Provider();
        $configurator = new EventConfigurator($provider, $container);
        $eventConfig = $this->getEventsConfig();
        $configurator->registerListeners($eventConfig);
        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        $this->assertCount(2, $listeners);
        $this->assertSame($eventConfig[0], $listeners[0]);
        $this->assertInstanceOf(Event::class, $listeners[1][0]);
        $this->assertSame('register', $listeners[1][1]);
    }

    private function getEventsConfig(): array
    {
        return [
            static function (Event $event) {
                $event->register(1);
            },
            Event::class => [
                [Event::class, 'register']
            ],
        ];
    }

    private function getContainer(array $instances): ContainerInterface
    {
        return new class($instances) implements ContainerInterface {
            private array $instances;

            public function __construct(array $instances)
            {
                $this->instances = $instances;
            }

            public function get($id)
            {
                return $this->instances[$id];
            }

            public function has($id)
            {
                return isset($this->instances[$id]);
            }
        };
    }
}
