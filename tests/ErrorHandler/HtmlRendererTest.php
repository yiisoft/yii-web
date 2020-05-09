<?php

namespace Yiisoft\Yii\Web\Tests\ErrorHandler;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Spiral\Files\Exception\WriteErrorException;
use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;

class HtmlRendererTest extends TestCase
{
    private const CUSTOM_TEMPLATES = [
        'exception' => __DIR__ . '/test-template-verbose.php',
        'error' => __DIR__ . '/test-template-non-verbose.php',
    ];

    public function testNonVerboseOutput(): void
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
    }

    public function testNonVerboseOutputWithCustomTemplate(): void
    {
        $templateFileContents = '<html><?php echo $throwable->getMessage();?></html>';
        $this->createTestTemplate(self::CUSTOM_TEMPLATES['error'], $templateFileContents);

        $renderer = new HtmlRenderer(self::CUSTOM_TEMPLATES);
        $request = new ServerRequest('GET', '/', ['Accept' => ['text/html']]);
        $renderer->setRequest($request);

        $exceptionMessage = 'exception-test-message';
        $exception = new \RuntimeException($exceptionMessage);

        $renderedOutput = $renderer->render($exception);
        $this->assertStringContainsString($exceptionMessage, $renderedOutput);
        $this->assertStringContainsString('<html>', $renderedOutput);
    }

    public function testVerboseOutputWithCustomTemplate(): void
    {
        $templateFileContents = '<html><?php echo $throwable->getMessage();?></html>';
        $this->createTestTemplate(self::CUSTOM_TEMPLATES['exception'], $templateFileContents);

        $renderer = new HtmlRenderer(self::CUSTOM_TEMPLATES);
        $request = new ServerRequest('GET', '/', ['Accept' => ['text/html']]);
        $renderer->setRequest($request);

        $exceptionMessage = 'exception-test-message';
        $exception = new \RuntimeException($exceptionMessage);

        $renderedOutput = $renderer->renderVerbose($exception);
        $this->assertStringContainsString($exceptionMessage, $renderedOutput);
        $this->assertStringContainsString('<html>', $renderedOutput);
    }

    public function testRenderTemplateThrowsExceptionWhenTemplateFileNotExists(): void
    {
        $exampleNonExistingFile = sprintf('%s.php', bin2hex(random_bytes(16)));
        $templates = [
            'error' => $exampleNonExistingFile
        ];

        $renderer = new HtmlRenderer($templates);
        $exception = new \Exception();
        $this->expectException(RuntimeException::class);
        $renderer->render($exception);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        foreach (self::CUSTOM_TEMPLATES as $template) {
            if (file_exists($template)) {
                $this->removeTestTemplate($template);
            }
        }
    }

    private function createTestTemplate(string $path, string $templateContents): void
    {
        if (!file_put_contents($path, $templateContents)) {
            throw new WriteErrorException(sprintf('Unable to create file at path %s', $path));
        }
    }

    private function removeTestTemplate(string $path): void
    {
        unlink($path);
    }
}
