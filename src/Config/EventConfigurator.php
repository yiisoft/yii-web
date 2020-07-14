<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Config;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\EventDispatcher\Provider\AbstractProviderConfigurator;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Injector\Injector;

final class EventConfigurator extends AbstractProviderConfigurator
{
    private Provider $listenerProvider;

    private ContainerInterface $container;

    public function __construct(Provider $listenerProvider, ContainerInterface $container)
    {
        $this->listenerProvider = $listenerProvider;
        $this->container = $container;
    }

    /**
     * @suppress PhanAccessMethodProtected
     *
     * @param array $eventListeners Event listener list in format ['eventName1' => [$listener1, $listener2, ...]]
     */
    public function registerListeners(array $eventListeners): void
    {
        foreach ($eventListeners as $eventName => $listeners) {
            if (!is_string($eventName)) {
                $message = 'Incorrect event listener format. Format with event name must be used.';

                throw new InvalidEventConfigurationFormatException($message);
            }

            if (!is_array($listeners)) {
                $previous = null;

                try {
                    $type = $this->isCallable($listeners) ? 'callable' : gettype($listeners);
                } catch (InvalidListenerConfigurationException $previous) {
                    $type = gettype($listeners);
                }
                $message = "Event listeners for $eventName must be an array, $type given.";

                throw new InvalidEventConfigurationFormatException($message, 0, $previous);
            }

            foreach ($listeners as $callable) {
                if (!$this->isCallable($callable)) {
                    $type = gettype($listeners);
                    $message = "Listener must be a callable. $type given.";

                    throw new InvalidListenerConfigurationException($message);
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

    private function isCallable($definition): bool
    {
        if (is_callable($definition)) {
            return true;
        }

        if (
            is_array($definition)
            && array_keys($definition) === [0, 1]
            && is_string($definition[0])
            && $this->container->has($definition[0])
        ) {
            try {
                $object = $this->container->get($definition[0]);

                return method_exists($object, $definition[1]);
            } catch (NotFoundExceptionInterface|ContainerExceptionInterface $exception) {
                $message = "Could not instantiate event listener or listener class has invalid configuration.";

                throw new InvalidListenerConfigurationException($message, 0, $exception);
            }
        }

        return false;
    }
}
