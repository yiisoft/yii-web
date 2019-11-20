<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Container;
use Yiisoft\Log\Logger;
use Yiisoft\Yii\Web\ErrorHandler\ErrorHandler;
use Yiisoft\Yii\Web\Middleware\ErrorCatcher;
use Yiisoft\Yii\Web\Tests\Middleware\Mock\MockRequestHandler;
use Yiisoft\Yii\Web\Tests\Middleware\Mock\MockThrowableRenderer;

class ErrorCatcherTest extends TestCase
{
    private const DEFAULT_RENDERER_RESPONSE = 'default-renderer-test';

    public function testAddedRenderer(): void
    {
        $factory = new Psr17Factory();
        $errorHandler = new ErrorHandler(new Logger(), new MockThrowableRenderer(self::DEFAULT_RENDERER_RESPONSE));
        $container = new Container();
        $mimeType = 'test/test';
        $containerId = 'testRenderer';
        $catcher = (new ErrorCatcher($factory, $errorHandler, $container))
            ->withAddedRenderer($mimeType, $containerId);
        $expectedRendererOutput = 'expectedRendereOutput';
        $container->set($containerId, new MockThrowableRenderer($expectedRendererOutput));
        $requestHandler = (new MockRequestHandler())->setHandleExcaption(new \RuntimeException());
        $response = $catcher->process(new ServerRequest('GET', '/', ['Accept' => [$mimeType]]), $requestHandler);
        $response->getBody()->rewind();
        $content = $response->getBody()->getContents();
        $this->assertNotSame(self::DEFAULT_RENDERER_RESPONSE, $content);
        $this->assertSame($expectedRendererOutput, $content);
    }

    public function testWithoutRenderers(): void
    {
        $factory = new Psr17Factory();
        $errorHandler = new ErrorHandler(new Logger(), new MockThrowableRenderer(self::DEFAULT_RENDERER_RESPONSE));
        $container = new Container();
        $catcher = (new ErrorCatcher($factory, $errorHandler, $container))
            ->withoutRenderers();
        $requestHandler = (new MockRequestHandler())->setHandleExcaption(new \RuntimeException());
        $response = $catcher->process(new ServerRequest('GET', '/', ['Accept' => ['test/html']]), $requestHandler);
        $response->getBody()->rewind();
        $content = $response->getBody()->getContents();
        $this->assertSame(self::DEFAULT_RENDERER_RESPONSE, $content);
    }

    public function testWithoutRenderer(): void
    {
        $factory = new Psr17Factory();
        $errorHandler = new ErrorHandler(new Logger(), new MockThrowableRenderer(self::DEFAULT_RENDERER_RESPONSE));
        $container = new Container();
        $catcher = (new ErrorCatcher($factory, $errorHandler, $container))
            ->withoutRenderers('text/html');
        $requestHandler = (new MockRequestHandler())->setHandleExcaption(new \RuntimeException());
        $response = $catcher->process(new ServerRequest('GET', '/', ['Accept' => ['test/html']]), $requestHandler);
        $response->getBody()->rewind();
        $content = $response->getBody()->getContents();
        $this->assertSame(self::DEFAULT_RENDERER_RESPONSE, $content);
    }

    public function testAdvancedAcceptHeader(): void
    {
        $factory = new Psr17Factory();
        $errorHandler = new ErrorHandler(new Logger(), new MockThrowableRenderer(self::DEFAULT_RENDERER_RESPONSE));
        $container = new Container();
        $mimeType = 'text/html;version=2';
        $containerId = 'testRenderer';
        $catcher = (new ErrorCatcher($factory, $errorHandler, $container))
            ->withAddedRenderer($mimeType, $containerId);
        $expectedRendererOutput = 'expectedRendereOutput';
        $container->set($containerId, new MockThrowableRenderer($expectedRendererOutput));
        $requestHandler = (new MockRequestHandler())->setHandleExcaption(new \RuntimeException());
        $response = $catcher->process(new ServerRequest('GET', '/', ['Accept' => ['text/html', $mimeType]]),
            $requestHandler);
        $response->getBody()->rewind();
        $content = $response->getBody()->getContents();
        $this->assertNotSame(self::DEFAULT_RENDERER_RESPONSE, $content);
        $this->assertSame($expectedRendererOutput, $content);
    }

    public function testDefaultContentType(): void
    {
        $factory = new Psr17Factory();
        $errorHandler = new ErrorHandler(new Logger(), new MockThrowableRenderer(self::DEFAULT_RENDERER_RESPONSE));
        $container = new Container();
        $containerId = 'testRenderer';
        $catcher = (new ErrorCatcher($factory, $errorHandler, $container))
            ->withAddedRenderer('*/*', $containerId);
        $expectedRendererOutput = 'expectedRendereOutput';
        $container->set($containerId, new MockThrowableRenderer($expectedRendererOutput));
        $requestHandler = (new MockRequestHandler())->setHandleExcaption(new \RuntimeException());
        $response = $catcher->process(new ServerRequest('GET', '/', ['Accept' => ['test/test']]),
            $requestHandler);
        $response->getBody()->rewind();
        $content = $response->getBody()->getContents();
        $this->assertNotSame(self::DEFAULT_RENDERER_RESPONSE, $content);
        $this->assertSame($expectedRendererOutput, $content);

    }
}
