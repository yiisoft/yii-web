<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Yii\Web\Event\AfterMiddleware;
use Yiisoft\Yii\Web\Event\BeforeMiddleware;
use Yiisoft\Yii\Web\MiddlewareDispatcher;

class MiddlewareDispatcherTest extends TestCase
{
    private MiddlewareDispatcher $middlewareDispatcher;
    private RequestHandlerInterface $fallbackHandlerMock;
    private EventDispatcherInterface $eventDispatcherMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $container = new Container(
            [
                EventDispatcherInterface::class => $this->eventDispatcherMock,
            ]
        );
        $this->fallbackHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->middlewareDispatcher = new MiddlewareDispatcher($container, $this->fallbackHandlerMock);
    }

    public function testAddThrowsInvalidArgumentExceptionWhenMiddlewareIsNotOfCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $incorrectInput = new \stdClass();

        $this->middlewareDispatcher->addMiddleware($incorrectInput);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAddAddsCallableToMiddlewareArrayWithoutThrowingException(): void
    {
        $callable = static function () {
            echo 'example function for testing purposes';
        };
        $this->middlewareDispatcher->addMiddleware($callable);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAddAddsMiddlewareInterfaceToMiddlewareArrayWithoutThrowingException(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $this->middlewareDispatcher->addMiddleware($middleware);
    }

    public function testDispatchExecutesMiddlewareStack(): void
    {
        $request = new ServerRequest('GET', '/');
        $this->fallbackHandlerMock
            ->expects($this->never())
            ->method('handle')
            ->with($request);
        $this->eventDispatcherMock
            ->expects($this->exactly(8)) // before and after each of two middlewares, each processed twice
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(BeforeMiddleware::class)],
                [$this->isInstanceOf(BeforeMiddleware::class)],
                [$this->isInstanceOf(AfterMiddleware::class)],
                [$this->isInstanceOf(AfterMiddleware::class)],
                [$this->isInstanceOf(BeforeMiddleware::class)],
                [$this->isInstanceOf(BeforeMiddleware::class)],
                [$this->isInstanceOf(AfterMiddleware::class)],
                [$this->isInstanceOf(AfterMiddleware::class)],
            );

        $middleware1 = static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');

            return $handler->handle($request);
        };
        $middleware2 = static function (ServerRequestInterface $request) {
            return new Response(200, [], null, '1.1', implode($request->getAttributes()));
        };

        $this->middlewareDispatcher->addMiddleware($middleware2)->addMiddleware($middleware1);
        $response = $this->middlewareDispatcher->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware1', $response->getReasonPhrase());
        // ensure that dispatcher could be called multiple times
        $this->middlewareDispatcher->dispatch($request);
    }
}
