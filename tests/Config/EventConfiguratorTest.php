<?php

namespace Yiisoft\Yii\Web\Tests\Config;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Yii\Web\Config\EventConfigurator;
use Yiisoft\Yii\Web\Session\Session;

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
        $this->assertInstanceOf(\Closure::class, $listeners[0]);
        $this->assertInstanceOf(\Closure::class, $listeners[1]);
    }

    public function testAddEventListenerInjection(): void
    {
        $event = new Event();

        $container = $this->getContainer([
            Event::class => new Event(),
            Session::class => new Session(),
        ]);
        $provider = new Provider();
        $configurator = new EventConfigurator($provider, $container);
        $eventConfig = $this->getEventsConfigWithDependency();
        $configurator->registerListeners($eventConfig);
        $listeners = iterator_to_array($provider->getListenersForEvent($event));
        $listeners[0]($event);

        $this->assertInstanceOf(Session::class, $event->registered()[0]);
    }

    private function getEventsConfig(): array
    {
        return [
            Event::class => [
                [Event::class, 'register'],
                static function (Event $event) {
                    $event->register(1);
                },
            ],
        ];
    }

    private function getEventsConfigWithDependency(): array
    {
        return [
            Event::class => [
                static function (Event $event, Session $session) {
                    $event->register($session);
                },
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
