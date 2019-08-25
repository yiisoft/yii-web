<?php


namespace Yiisoft\Yii\Web\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Di\Container;
use PHPUnit_Framework_MockObject_MockObject;
use Yiisoft\Yii\Web\Emitter\SapiEmitter;
use Yiisoft\Yii\Web\MiddlewareDispatcher;

class MiddlewareDispatcherTest extends TestCase
{
    /**
     * @var MiddlewareDispatcher
     */
    private $middlewareDispatcher;

    /**
     * @var Container|PHPUnit_Framework_MockObject_MockObject
     */
    private $containerMock;

    /**
     * @var RequestHandlerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $fallbackHandlerMock;

    /**
     * @var MiddlewareInterface[]|PHPUnit_Framework_MockObject_MockObject[]
     */
    private $middlewareMocks;

    public function setUp()
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

    /**
     * @test
     */
    public function addThrowsInvalidArgumentExceptionWhenMiddlewareIsNotOfCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $exampleInput = new SapiEmitter();

        $this->middlewareDispatcher->add($exampleInput);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function addAddsCallableToMiddlewareArrayWithoutThrowingException()
    {
        $callable = function () {
            echo 'example function for testing purposes';
        };
        $this->middlewareDispatcher->add($callable);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function addAddsMiddlewareInterfaceToMiddlewareArrayWithoutThrowingException()
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $this->middlewareDispatcher->add($middleware);
    }

    /**
     * @test
     */
    public function handleCallsFallbackHandlerWhenPointerValueEqualsMiddlewareQueueSize()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $this->middlewareDispatcher->handle($request); //pointer is incremented to 1
        $this->middlewareDispatcher->handle($request); //pointer is incremented to 2

        $this->fallbackHandlerMock
            ->expects($this->once())
            ->method('handle')
            ->with($request);

        $this->middlewareDispatcher->handle($request); //pointer value equals middleware queue size (2)
    }

    /**
     * @test
     */
    public function handleCallsConsecutiveMiddlewareFromQueueToProcessRequest()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $this->fallbackHandlerMock
            ->expects($this->never())
            ->method('handle')
            ->with($request);

        foreach ($this->middlewareMocks as $middlewareMock) {
            $middlewareMock
                ->expects($this->once())
                ->method('process')
                ->with($request, $this->middlewareDispatcher);
            $this->middlewareDispatcher->handle($request);
        }
    }
}
