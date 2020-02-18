<?php

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Yii\Web\Emitter\SapiEmitter;
use Yiisoft\Yii\Web\MiddlewareDispatcher;

class MiddlewareDispatcherTest extends TestCase
{
    /**
     * @var MiddlewareDispatcher
     */
    private $middlewareDispatcher;

    /**
     * @var Container
     */
    private $containerMock;

    /**
     * @var RequestHandlerInterface
     */
    private $fallbackHandlerMock;

    /**
     * @var MiddlewareInterface[]
     */
    private $middlewareMocks;

    public function setUp(): void
    {
        parent::setUp();
        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->fallbackHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->middlewareMocks = [
            $this->createMock(MiddlewareInterface::class),
            $this->createMock(MiddlewareInterface::class)
        ];
        $this->middlewareDispatcher = new MiddlewareDispatcher($this->middlewareMocks, $this->containerMock, $this->fallbackHandlerMock);
    }

    public function testConstructThrowsExceptionWhenMiddlewaresAreNotDefined(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MiddlewareDispatcher(
            [],
            $this->containerMock,
            $this->fallbackHandlerMock
        );
    }

    public function testAddThrowsInvalidArgumentExceptionWhenMiddlewareIsNotOfCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $exampleInput = new SapiEmitter();

        $this->middlewareDispatcher->addMiddleware($exampleInput);
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

    public function testDispatchCallsMiddlewareFullStack(): void
    {
        $request = new ServerRequest('GET', '/');
        $this->fallbackHandlerMock
            ->expects($this->never())
            ->method('handle')
            ->with($request);

        $middleware1 = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');
            return $handler->handle($request);
        };
        $middleware2 = function (ServerRequestInterface $request) {
            return new Response(200, [], null, '1.1', implode($request->getAttributes()));
        };
        $middlewareDispatcher = new MiddlewareDispatcher([$middleware1, $middleware2], $this->containerMock, $this->fallbackHandlerMock);
        $response = $middlewareDispatcher->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware1', $response->getReasonPhrase());
        // ensure that dispatcher could be called multiple times
        $this->middlewareDispatcher->dispatch($request);
    }
}
