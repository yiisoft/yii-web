<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\ErrorHandler;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Spiral\Files\Exception\WriteErrorException;
use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;

class HtmlRendererTest extends TestCase
{
    private const CUSTOM_TEMPLATES = [
        'exception' => __DIR__ . '/test-template-verbose.php',
        'error' => __DIR__ . '/test-template-non-verbose.php',
    ];

    private const DEFAULT_TEMPLATES = [
        'default' => [
            'callStackItem',
            'error',
            'exception',
            'previousException'
        ],
        'path' => __DIR__ . '/../../src/ErrorHandler/templates',
    ];

    public function testNonVerboseOutput(): void
    {
        $renderer = new HtmlRenderer(self::DEFAULT_TEMPLATES);
        $request = $this->getServerRequestMock();
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
        $renderer = new HtmlRenderer(self::DEFAULT_TEMPLATES);
        $request = $this->getServerRequestMock();
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

        $templates = $this->getTemplateConfigParamsForCustomTemplates();
        $renderer = new HtmlRenderer($templates);
        $request = $this->getServerRequestMock();
        $renderer->setRequest($request);

        $exceptionMessage = 'exception-test-message';
        $exception = new \RuntimeException($exceptionMessage);

        $renderedOutput = $renderer->render($exception);
        $this->assertStringContainsString("<html>$exceptionMessage</html>", $renderedOutput);
    }

    public function testVerboseOutputWithCustomTemplate(): void
    {
        $templateFileContents = '<html><?php echo $throwable->getMessage();?></html>';
        $this->createTestTemplate(self::CUSTOM_TEMPLATES['exception'], $templateFileContents);

        $templates = $this->getTemplateConfigParamsForCustomTemplates();
        $renderer = new HtmlRenderer($templates);
        $request = $this->getServerRequestMock();
        $renderer->setRequest($request);

        $exceptionMessage = 'exception-test-message';
        $exception = new \RuntimeException($exceptionMessage);

        $renderedOutput = $renderer->renderVerbose($exception);
        $this->assertStringContainsString("<html>$exceptionMessage</html>", $renderedOutput);
    }

    public function testRenderTemplateThrowsExceptionWhenTemplateFileNotExists(): void
    {
        $exampleNonExistingFile = '_not_found_.php';

        $templates = [
            'error' => $exampleNonExistingFile
        ];
        $templates = array_merge(self::DEFAULT_TEMPLATES, $templates);

        $renderer = new HtmlRenderer($templates);
        $request = $this->getServerRequestMock();
        $renderer->setRequest($request);
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

    private function getServerRequestMock(): ServerRequestInterface
    {
        $acceptHeader = [
            'text/html'
        ];
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);
        $serverRequestMock
            ->method('getHeader')
            ->with('Accept')
            ->willReturn($acceptHeader);

        $serverRequestMock
            ->method('getHeaders')
            ->willReturn(
                [
                    'Accept' => $acceptHeader
                ]
            );

        $serverRequestMock
            ->method('getMethod')
            ->willReturn('GET');

        return $serverRequestMock;
    }

    private function getTemplateConfigParamsForCustomTemplates(): array
    {
        return array_merge(self::CUSTOM_TEMPLATES, self::DEFAULT_TEMPLATES);
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
