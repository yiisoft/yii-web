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

    public function registerListeners(array $eventsListeners): void
    {
        foreach ($eventsListeners as $eventName => $listeners) {
            if (!is_string($eventName)) {
                throw new \RuntimeException('Incorrect event listener format. Format with event name must be used.');
            }

            if (!is_array($listeners)) {
                $type = is_callable($listeners) ? 'callable' : gettype($listeners);
                throw new \RuntimeException("Event listeners for $eventName must be an array, $type detected.");
            }
            foreach ($listeners as $callable) {
                if (!is_callable($callable)) {
                    $type = gettype($listeners);
                    throw new \RuntimeException("Listener must be a callable. $type given.");
                }
                if (is_array($callable) && !is_object($callable[0])) {
                    $callable = [$this->container->get($callable[0]), $callable[1]];
                }

                $this->listenerProvider
                    ->attach(
                        fn ($event) => (new Injector($this->container))->invoke($callable, [$event]),
                        $eventName
                    );
            }
        }
    }
}
