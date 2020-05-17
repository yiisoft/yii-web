<?php


namespace Yiisoft\Yii\Web\Tests\ErrorHandler;


use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Yiisoft\Yii\Web\ErrorHandler\ErrorHandler;
use Yiisoft\Yii\Web\ErrorHandler\ThrowableRendererInterface;

class ErrorHandlerTest extends TestCase
{
    /** @var ErrorHandler  */
    private $errorHandler;

    /** @var LoggerInterface  */
    private $loggerMock;

    /** @var ThrowableRendererInterface  */
    private $throwableRendererMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->throwableRendererMock = $this->createMock(ThrowableRendererInterface::class);
        $this->errorHandler = new ErrorHandler($this->loggerMock, $this->throwableRendererMock);
    }

    public function testHandleCaughtThrowableCallsDefaultRendererWhenNonePassed(): void
    {
        $throwable = new \RuntimeException();

        $this
            ->throwableRendererMock
            ->expects($this->once())
            ->method('renderVerbose')
            ->with($throwable);


        $this->errorHandler->handleCaughtThrowable($throwable);
    }

    public function testHandleCaughtThrowableCallsPassedRenderer(): void
    {
        $throwable = new \RuntimeException();
        $throwableRendererMock = $this->createMock(ThrowableRendererInterface::class);

        $this
            ->throwableRendererMock
            ->expects($this->never())
            ->method('renderVerbose')
            ->with($throwable);

        $throwableRendererMock
            ->expects($this->once())
            ->method('renderVerbose')
            ->with($throwable);

        $this->errorHandler->handleCaughtThrowable($throwable, $throwableRendererMock);
    }

    public function testHandleCaughtThrowableWithExposedDetailsCallsRenderVerbose(): void
    {
        $throwable = new \RuntimeException();
        $this
            ->throwableRendererMock
            ->expects($this->once())
            ->method('renderVerbose')
            ->with($throwable);

        $errorHandler = $this->errorHandler->withExposedDetails();
        $errorHandler->handleCaughtThrowable($throwable);
    }

    public function testHandleCaughtThrowableWithoutExposedDetailsCallsRender(): void
    {
        $throwable = new \RuntimeException();
        $this
            ->throwableRendererMock
            ->expects($this->once())
            ->method('render')
            ->with($throwable);

        $errorHandler = $this->errorHandler->withoutExposedDetails();
        $errorHandler->handleCaughtThrowable($throwable);
    }
}
