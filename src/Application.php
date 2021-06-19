<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Yii\Web\Event\AfterEmit;
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
    private RequestHandlerInterface $fallbackHandler;

    public function __construct(
        MiddlewareDispatcher $dispatcher,
        EventDispatcherInterface $eventDispatcher,
        RequestHandlerInterface $fallbackHandler
    ) {
        $this->dispatcher = $dispatcher;
        $this->eventDispatcher = $eventDispatcher;
        $this->fallbackHandler = $fallbackHandler;
    }

    public function start(): void
    {
        $this->eventDispatcher->dispatch(new ApplicationStartup());
    }

    public function shutdown(): void
    {
        $this->eventDispatcher->dispatch(new ApplicationShutdown());
    }

    public function afterEmit(?ResponseInterface $response): void
    {
        $this->eventDispatcher->dispatch(new AfterEmit($response));
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->eventDispatcher->dispatch(new BeforeRequest($request));

        try {
            return $response = $this->dispatcher->dispatch($request, $this->fallbackHandler);
        } finally {
            $this->eventDispatcher->dispatch(new AfterRequest($response ?? null));
        }
    }
}
