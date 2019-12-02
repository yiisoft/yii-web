<?php

namespace Yiisoft\Yii\Web\Tests\ErrorHandler;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;

class HtmlRendererTest extends TestCase
{
    public function testNonVerboseOutput(): void
    {
        $renderer = new HtmlRenderer();
        $request = new ServerRequest('GET', '/', ['Accept' => ['text/html']]);
        $renderer->setRequest($request);
        $exceptionMessage = 'exception-test-message';
        $exception = new \RuntimeException($exceptionMessage);
        $renderedOutput = $renderer->render($exception);
        $this->assertContains('<html', $renderedOutput);
        $this->assertNotContains(RuntimeException::class, $renderedOutput);
        $this->assertNotContains($exceptionMessage, $renderedOutput);
    }

    public function testVerboseOutput(): void
    {
        $renderer = new HtmlRenderer();
        $request = new ServerRequest('GET', '/', ['Accept' => ['text/html']]);
        $renderer->setRequest($request);
        $exceptionMessage = 'exception-test-message';
        $exception = new \RuntimeException($exceptionMessage);
        $renderedOutput = $renderer->renderVerbose($exception);
        $this->assertContains('<html', $renderedOutput);
        $this->assertContains(RuntimeException::class, $renderedOutput);
        $this->assertContains($exceptionMessage, $renderedOutput);
    }
}
