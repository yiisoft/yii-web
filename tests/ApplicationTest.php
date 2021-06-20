<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests;

use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Yii\Web\Application;
use Yiisoft\Yii\Web\Event\AfterEmit;
use Yiisoft\Yii\Web\Event\AfterRequest;
use Yiisoft\Yii\Web\Event\ApplicationShutdown;
use Yiisoft\Yii\Web\Event\ApplicationStartup;
use Yiisoft\Yii\Web\Event\BeforeRequest;
use Yiisoft\Yii\Web\NotFoundHandler;
use Yiisoft\Yii\Web\Tests\Mock\MockEventDispatcher;
use Yiisoft\Yii\Web\Tests\Mock\MockMiddleware;

final class ApplicationTest extends TestCase
{
    public function testStartMethodDispatchEvent(): void
    {
        $eventDispatcher = new MockEventDispatcher();
        $this->createApplication($eventDispatcher)->start();
        $this->assertEquals([ApplicationStartup::class], $eventDispatcher->getClassesEvents());
    }

    public function testShutdownMethodDispatchEvent(): void
    {
        $eventDispatcher = new MockEventDispatcher();
        $this->createApplication($eventDispatcher)->shutdown();
        $this->assertEquals([ApplicationShutdown::class], $eventDispatcher->getClassesEvents());
    }

    public function testAfterEmitMethodDispatchEvent(): void
    {
        $eventDispatcher = new MockEventDispatcher();
        $this->createApplication($eventDispatcher)->afterEmit(null);
        $this->assertEquals([AfterEmit::class], $eventDispatcher->getClassesEvents());
        $this->assertNull($eventDispatcher->getFirstEvent()->getResponse());
    }

    public function testAfterEmitMethodWithResponseDispatchEvent(): void
    {
        $eventDispatcher = new MockEventDispatcher();
        $this->createApplication($eventDispatcher)->afterEmit(new Response());
        $this->assertEquals([AfterEmit::class], $eventDispatcher->getClassesEvents());
        $this->assertInstanceOf(Response::class, $eventDispatcher->getFirstEvent()->getResponse());
    }

    public function testHandleMethodDispatchEvents(): void
    {
        $eventDispatcher = new MockEventDispatcher();
        $response = $this->createApplication($eventDispatcher)->handle($this->createRequest());
        $this->assertEquals(
            [
                BeforeRequest::class,
                BeforeMiddleware::class,
                AfterMiddleware::class,
                AfterRequest::class,
            ],
            $eventDispatcher->getClassesEvents()
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testHandleMethodWithExceptionDispatchEvents(): void
    {
        $eventDispatcher = new MockEventDispatcher();

        try {
            $this->createApplication($eventDispatcher, true)->handle($this->createRequest());
        } catch (Exception $e) {
        }

        $this->assertEquals(
            [
                BeforeRequest::class,
                BeforeMiddleware::class,
                AfterMiddleware::class,
                AfterRequest::class,
            ],
            $eventDispatcher->getClassesEvents()
        );
    }

    public function testAfterRequestWithResponseDispatchEvent(): void
    {
        $eventDispatcher = new MockEventDispatcher();
        $this->createApplication($eventDispatcher)->handle($this->createRequest());
        $this->assertInstanceOf(Response::class, $eventDispatcher->getLastEvent()->getResponse());
    }

    public function testAfterRequestWithExceptionDispatchEvent(): void
    {
        $eventDispatcher = new MockEventDispatcher();

        try {
            $this->createApplication($eventDispatcher, true)->handle($this->createRequest());
        } catch (Exception $exception) {
        }

        $this->assertNull($eventDispatcher->getLastEvent()->getResponse());
    }

    private function createApplication(EventDispatcherInterface $eventDispatcher, bool $throwException = false): Application
    {
        if ($throwException === false) {
            $middlewareDispatcher = $this->createMiddlewareDispatcher(
                $this->createContainer($eventDispatcher)
            );
        } else {
            $middlewareDispatcher = $this->createMiddlewareDispatcherWithException(
                $this->createContainer($eventDispatcher)
            );
        }

        return new Application(
            $middlewareDispatcher,
            $eventDispatcher,
            new NotFoundHandler(new Psr17Factory())
        );
    }

    private function createMiddlewareDispatcher(Container $container): MiddlewareDispatcher
    {
        return (new MiddlewareDispatcher(new MiddlewareFactory($container), $container->get(EventDispatcherInterface::class)))
            ->withMiddlewares([
                static fn () => new MockMiddleware(400),
            ]);
    }

    private function createMiddlewareDispatcherWithException(Container $container): MiddlewareDispatcher
    {
        return (new MiddlewareDispatcher(new MiddlewareFactory($container), $container->get(EventDispatcherInterface::class)))
            ->withMiddlewares([
                static fn () => new class() implements MiddlewareInterface {
                    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                    {
                        throw new Exception();
                    }
                },
            ]);
    }

    private function createContainer(EventDispatcherInterface $eventDispatcher): Container
    {
        return new Container(
            [
                ResponseFactoryInterface::class => new Psr17Factory(),
                EventDispatcherInterface::class => $eventDispatcher,
            ]
        );
    }

    private function createRequest(): ServerRequest
    {
        return new ServerRequest('GET', 'http://test.com');
    }
}
