<?php

namespace Yiisoft\Yii\Web;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
    private EventDispatcherInterface $eventDispatcher;
    private ErrorHandler $errorHandler;

    public function __construct(
        MiddlewareDispatcher $dispatcher,
        ErrorHandler $errorHandler,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->dispatcher = $dispatcher;
        $this->errorHandler = $errorHandler;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function start(): void
    {
        $this->errorHandler->register();
        $this->eventDispatcher->dispatch(new ApplicationStartup());
    }

    public function shutdown(): void
    {
        $this->eventDispatcher->dispatch(new ApplicationShutdown());
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->withHeader('Yii-Request-ID', uniqid('yii-request-', true));
        $this->eventDispatcher->dispatch(new BeforeRequest($request));
        $response = $this->dispatcher->dispatch($request);
        $this->eventDispatcher->dispatch(new AfterRequest($response));
        return $response;
    }
}
