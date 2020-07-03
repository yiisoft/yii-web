<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\Test\TestLogger;
use Yiisoft\Di\Container;
use Yiisoft\Yii\Web\Application;
use Yiisoft\Yii\Web\ErrorHandler\ErrorHandler;
use Yiisoft\Yii\Web\Event\AfterEmit;
use Yiisoft\Yii\Web\Event\AfterMiddleware;
use Yiisoft\Yii\Web\Event\AfterRequest;
use Yiisoft\Yii\Web\Event\ApplicationShutdown;
use Yiisoft\Yii\Web\Event\ApplicationStartup;
use Yiisoft\Yii\Web\Event\BeforeMiddleware;
use Yiisoft\Yii\Web\Event\BeforeRequest;
use Yiisoft\Yii\Web\MiddlewareDispatcher;
use Yiisoft\Yii\Web\Tests\Middleware\Mock\MockThrowableRenderer;
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
                AfterRequest::class

            ],
            $eventDispatcher->getClassesEvents()
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    private function createApplication(EventDispatcherInterface $eventDispatcher): Application
    {
        return new Application(
            $this->createMiddlewareDispatcher(
                $this->createContainer($eventDispatcher)
            ),
            $this->createErrorHandler(),
            $eventDispatcher
        );
    }

    private function createMiddlewareDispatcher(Container $container): MiddlewareDispatcher
    {
        return (new MiddlewareDispatcher($container))
            ->addMiddleware(
                new MockMiddleware(400)
            );
    }

    private function createContainer(EventDispatcherInterface $eventDispatcher): Container
    {
        return new Container(
            [
                ResponseFactoryInterface::class => new Psr17Factory(),
                EventDispatcherInterface::class => $eventDispatcher
            ]
        );
    }

    private function createErrorHandler(): ErrorHandler
    {
        return new ErrorHandler(
            new TestLogger(),
            new MockThrowableRenderer('')
        );
    }

    private function createRequest(): ServerRequest
    {
        return new ServerRequest('GET', 'http://test.com');
    }
}
