<?php

namespace Yiisoft\Yii\Web\Tests;

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

        $this->middlewareDispatcher->add($exampleInput);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAddAddsCallableToMiddlewareArrayWithoutThrowingException(): void
    {
        $callable = static function () {
            echo 'example function for testing purposes';
        };
        $this->middlewareDispatcher->add($callable);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAddAddsMiddlewareInterfaceToMiddlewareArrayWithoutThrowingException(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $this->middlewareDispatcher->add($middleware);
    }

    public function testDispatchCallsMiddlewareFromQueueToProcessRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $this->fallbackHandlerMock
            ->expects($this->never())
            ->method('handle')
            ->with($request);

        $this->middlewareMocks[0]
            ->expects($this->exactly(2))
            ->method('process')
            ->with($request, $this->middlewareDispatcher);

        // TODO: test that second middleware is called as well

        $this->middlewareDispatcher->dispatch($request);

        // ensure that dispatcher could be called multiple times
        $this->middlewareDispatcher->dispatch($request);
    }
}
