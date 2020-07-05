<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\Web\Middleware\Redirect;

final class RedirectTest extends TestCase
{
    public function testInvalidArguments(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->createRedirectMiddleware()->process($this->createRequest(), $this->createRequestHandler());
    }

    public function testGenerateUri(): void
    {
        $middleware = $this->createRedirectMiddleware()
            ->toRoute('test/route', [
                'param1' => 1,
                'param2' => 2,
            ]);

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $header = $response->getHeader('Location');

        $this->assertSame($header[0], 'test/route?param1=1&param2=2');
    }

    public function testTemporaryReturnCode303(): void
    {
        $middleware = $this->createRedirectMiddleware()
            ->toRoute('test/route')
            ->temporary();

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());

        $this->assertSame($response->getStatusCode(), 303);
    }

    public function testPermanentReturnCode301(): void
    {
        $middleware = $this->createRedirectMiddleware()
            ->toRoute('test/route')
            ->permanent();

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());

        $this->assertSame($response->getStatusCode(), 301);
    }

    public function testStatusReturnCode400(): void
    {
        $middleware = $this->createRedirectMiddleware()
            ->toRoute('test/route')
            ->status(400);

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());

        $this->assertSame($response->getStatusCode(), 400);
    }

    public function testSetUri(): void
    {
        $middleware = $this->createRedirectMiddleware()
            ->toUrl('test/custom/route');

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $header   = $response->getHeader('Location');

        $this->assertSame($header[0], 'test/custom/route');
    }

    private function createRequestHandler(): RequestHandlerInterface
    {
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler
            ->method('handle')
            ->willReturn(new Response(200));

        return $requestHandler;
    }

    private function createRequest(string $method = Method::GET, string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }

    private function createRedirectMiddleware(): Redirect
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(fn ($name, $params) => $name . '?' . http_build_query($params));

        return new Redirect(new Psr17Factory(), $urlGenerator);
    }
}
