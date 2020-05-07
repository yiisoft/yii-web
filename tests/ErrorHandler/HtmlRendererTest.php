<?php

namespace Yiisoft\Yii\Web\Tests\ErrorHandler;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;

class HtmlRendererTest extends TestCase
{
    private $renderer;

    private $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->renderer = new HtmlRenderer();
        $this->request = new ServerRequest('GET', '/', ['Accept' => ['text/html']]);
        $this->renderer->setRequest($this->request);
    }

    public function testNonVerboseOutput(): void
    {
        $exceptionMessage = 'exception-test-message';
        $exception = new \RuntimeException($exceptionMessage);
        $renderedOutput = $this->renderer->render($exception);
        $this->assertStringContainsString('<html', $renderedOutput);
        $this->assertStringNotContainsString(RuntimeException::class, $renderedOutput);
        $this->assertStringNotContainsString($exceptionMessage, $renderedOutput);
    }

    public function testVerboseOutput(): void
    {
        $exceptionMessage = 'exception-test-message';
        $exception = new \RuntimeException($exceptionMessage);
        $renderedOutput = $this->renderer->renderVerbose($exception);
        $this->assertStringContainsString('<html', $renderedOutput);
        $this->assertStringContainsString(RuntimeException::class, $renderedOutput);
        $this->assertStringContainsString($exceptionMessage, $renderedOutput);
    }

    public function testNonVerboseOutputWithCustomTemplate(): void
    {
        $exceptionMessage = 'exception-test-message';
        $exception = new \RuntimeException($exceptionMessage);
        $templatePath = __DIR__ . '/';
        $template = 'testTemplate';
        $templateContent = '<?php echo $throwable ?>';
        $this->createTestTemplate($templatePath, $template, $templateContent);

        $renderedOutput = $this->renderer->render($exception, $template, $templatePath);
        $this->removeTestTemplate($templatePath, $template);
        $this->assertStringContainsString($exceptionMessage, $renderedOutput);
    }

    public function testVerboseOutputWithCustomTemplate(): void
    {
        $exceptionMessage = 'exception-test-message';
        $exception = new \RuntimeException($exceptionMessage);
        $templatePath = __DIR__ . '/';
        $template = 'testTemplate';
        $templateContent = '<?php echo $throwable ?>';
        $this->createTestTemplate($templatePath, $template, $templateContent);

        $renderedOutput = $this->renderer->renderVerbose($exception, $template, $templatePath);
        $this->removeTestTemplate($templatePath, $template);
        $this->assertStringContainsString($exceptionMessage, $renderedOutput);
    }

    private function createTestTemplate(string $templatePath, string $template, $templateContent): void
    {
        $fullPath = $templatePath . $template . '.php';
        $file = fopen($fullPath, 'w');
        fwrite($file, $templateContent);
        fclose($file);
    }

    private function removeTestTemplate(string $templatePath, string $template): void
    {
        unlink($templatePath . $template . '.php');
    }
}
