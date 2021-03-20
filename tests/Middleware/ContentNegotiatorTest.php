<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\DataResponse\Formatter\HtmlDataResponseFormatter;
use Yiisoft\DataResponse\Formatter\XmlDataResponseFormatter;
use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;
use Yiisoft\Yii\Web\Middleware\ContentNegotiator;

class ContentNegotiatorTest extends TestCase
{
    public function testAcceptHtml(): void
    {
        $middleware = new ContentNegotiator($this->getContentFormatters());
        $request = new ServerRequest('GET', '/', ['Accept' => 'text/html']);
        $response = $middleware->process($request, $this->getHandler('<div>Hello</div>'));
        $response->getBody()->rewind();
        $content = $response->getBody()->getContents();

        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertTrue($response->hasResponseFormatter());
        $this->assertSame('<div>Hello</div>', $content);
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type')[0]);
    }

    public function testAcceptXml(): void
    {
        $middleware = new ContentNegotiator($this->getContentFormatters());
        $request = new ServerRequest('GET', '/', ['Accept' => 'application/xml']);
        $response = $middleware->process($request, $this->getHandler('Hello'));
        $response->getBody()->rewind();
        $content = $response->getBody()->getContents();

        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertTrue($response->hasResponseFormatter());
        $this->assertSame($this->xml('<response>Hello</response>'), $content);
        $this->assertSame('application/xml; UTF-8', $response->getHeader('Content-Type')[0]);
    }

    public function testAcceptJson(): void
    {
        $middleware = new ContentNegotiator($this->getContentFormatters());
        $request = new ServerRequest('GET', '/', ['Accept' => 'application/json']);
        $response = $middleware->process($request, $this->getHandler(['test' => 'Hello']));
        $response->getBody()->rewind();
        $content = $response->getBody()->getContents();

        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertTrue($response->hasResponseFormatter());
        $this->assertSame('{"test":"Hello"}', $content);
        $this->assertSame('application/json', $response->getHeader('Content-Type')[0]);
    }

    public function testWrongContentFormattersInConstructor()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectErrorMessage('Invalid formatter type.');
        new ContentNegotiator($this->getWrongContentFormatters());
    }

    public function testWrongContentFormattersInSetter()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectErrorMessage('Invalid formatter type.');
        $middleware = new ContentNegotiator($this->getContentFormatters());
        $middleware->withContentFormatters($this->getWrongContentFormatters());
    }

    private function getHandler($data): RequestHandlerInterface
    {
        return new class($data) implements RequestHandlerInterface {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $factory = new DataResponseFactory(new Psr17Factory());
                return $factory->createResponse($this->data);
            }
        };
    }

    private function getContentFormatters(): array
    {
        return [
            'text/html' => new HtmlDataResponseFormatter(),
            'application/xml' => new XmlDataResponseFormatter(),
            'application/json' => new JsonDataResponseFormatter(),
        ];
    }

    private function getWrongContentFormatters(): array
    {
        return [
            'text/html' => new HtmlDataResponseFormatter(),
            'application/xml' => new \StdClass(),
            'application/json' => new JsonDataResponseFormatter(),
        ];
    }

    private function xml(string $data, string $version = '1.0', string $encoding = 'UTF-8'): string
    {
        $startLine = sprintf('<?xml version="%s" encoding="%s"?>', $version, $encoding);
        return $startLine . "\n" . preg_replace('/(?!item)\s(?!key)/', '', $data) . "\n";
    }
}
