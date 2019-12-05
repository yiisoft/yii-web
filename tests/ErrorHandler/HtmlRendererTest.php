<?php

namespace Yiisoft\Yii\Web\Tests\ErrorHandler;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;

class HtmlRendererTest extends TestCase
{
    /*public function testNonVerboseOutput(): void
    {
        $renderer = new HtmlRenderer();
        $request = new ServerRequest('GET', '/', ['Accept' => ['text/html']]);
        $renderer->setRequest($request);
        $exceptionMessage = 'exception-test-message';
        $exception = new \RuntimeException($exceptionMessage);
        $renderedOutput = $renderer->render($exception);
        $this->assertStringContainsString('<html', $renderedOutput);
        $this->assertStringNotContainsString(RuntimeException::class, $renderedOutput);
        $this->assertStringNotContainsString($exceptionMessage, $renderedOutput);
    }

    public function testVerboseOutput(): void
    {
        $renderer = new HtmlRenderer();
        $request = new ServerRequest('GET', '/', ['Accept' => ['text/html']]);
        $renderer->setRequest($request);
        $exceptionMessage = 'exception-test-message';
        $exception = new \RuntimeException($exceptionMessage);
        $renderedOutput = $renderer->renderVerbose($exception);
        $this->assertStringContainsString('<html', $renderedOutput);
        $this->assertStringContainsString(RuntimeException::class, $renderedOutput);
        $this->assertStringContainsString($exceptionMessage, $renderedOutput);
    }*/
}
