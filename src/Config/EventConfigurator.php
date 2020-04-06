<?php

namespace Yiisoft\Yii\Web\Config;

use Psr\Container\ContainerInterface;
use Yiisoft\EventDispatcher\Provider\AbstractProviderConfigurator;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Injector\Injector;

class EventConfigurator extends AbstractProviderConfigurator
{
    private Provider $listenerProvider;

    private ContainerInterface $container;

    public function __construct(Provider $listenerProvider, ContainerInterface $container)
    {
        $this->listenerProvider = $listenerProvider;
        $this->container = $container;
    }

    public function registerListeners(array $listeners): void
    {
        foreach ($listeners as $eventName => $listener) {
            if (is_string($eventName)) {
                foreach ($listener as $callable) {
                    if (!is_callable($callable)) {
                        throw new \RuntimeException('Listener must be a callable.');
                    }
                    if (is_array($callable) && !is_object($callable[0])) {
                        $callable = [$this->container->get($callable[0]), $callable[1]];
                    }
                    $this->listenerProvider
                        ->attach(fn ($event) => (new Injector($this->container))->invoke($callable, [$event]), $eventName);
                }
            } else {
                throw new \RuntimeException('Incorrect event listener format. Format with event name must be used.');
            }
        }
    }
}
