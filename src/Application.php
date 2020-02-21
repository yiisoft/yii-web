<?php

namespace Yiisoft\Yii\Web;

use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Yii\Web\Emitter\EmitterInterface;
use Yiisoft\Yii\Web\ErrorHandler\ErrorHandler;
use Yiisoft\Yii\Web\Event\AfterRequest;
use Yiisoft\Yii\Web\Event\ApplicationShutdown;
use Yiisoft\Yii\Web\Event\ApplicationStartup;
use Yiisoft\Yii\Web\Event\BeforeRequest;

/**
 * Application is the entry point for a web application.
 * For more details and usage information on Application, see the [guide article on
 * applications](guide:structure-applications).
 */
final class Application
{
    private MiddlewareDispatcher $dispatcher;
    private EmitterInterface $emitter;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        MiddlewareDispatcher $dispatcher,
        EmitterInterface $emitter,
        ErrorHandler $errorHandler,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->dispatcher = $dispatcher;
        $this->emitter = $emitter;
        $this->eventDispatcher = $eventDispatcher;

        $errorHandler->register();
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): bool
    {
        $this->eventDispatcher->dispatch(new ApplicationStartup());

        try {
            $this->eventDispatcher->dispatch(new BeforeRequest($request));
            $response = $this->dispatcher->dispatch($request);
            $this->eventDispatcher->dispatch(new AfterRequest($response));

            return $this->emitter->emit($response, $request->getMethod() === Method::HEAD);
        } finally {
            $this->eventDispatcher->dispatch(new ApplicationShutdown());
        }
    }
}
