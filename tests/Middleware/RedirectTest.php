<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\Method;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\Web\Middleware\Redirect;

final class RedirectTest extends TestCase
{
    /**
     * @test
     */
    public function invalidArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->createRedirectMiddleware()->process($this->createRequest(), $this->createRequestHandler());
    }

    /**
     * @test
     */
    public function generateUri()
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

    /**
     * @test
     */
    public function temporaryReturnCode302()
    {
        $middleware = $this->createRedirectMiddleware()
            ->toRoute('test/route')
            ->temporary();

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());

        $this->assertSame($response->getStatusCode(), 302);
    }

    /**
     * @test
     */
    public function permanentReturnCode301()
    {
        $middleware = $this->createRedirectMiddleware()
            ->toRoute('test/route')
            ->permanent();

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());

        $this->assertSame($response->getStatusCode(), 301);
    }

    /**
     * @test
     */
    public function statusReturnCode400()
    {
        $middleware = $this->createRedirectMiddleware()
            ->toRoute('test/route')
            ->status(400);

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());

        $this->assertSame($response->getStatusCode(), 400);
    }

    /**
     * @test
     */
    public function setUri()
    {
        $middleware = $this->createRedirectMiddleware()
            ->toUrl('test/custom/route');

        $response = $middleware->process($this->createRequest(), $this->createRequestHandler());
        $header   = $response->getHeader('Location');

        $this->assertSame($header[0], 'test/custom/route');
    }

    private function createRequestHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };
    }

    private function createRequest(string $method = Method::GET, string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }

    private function createUrlGenerator(): UrlGeneratorInterface
    {
        return new class implements UrlGeneratorInterface {
            public function generate(string $name, array $parameters = []): string
            {
                return $name . '?' . http_build_query($parameters);
            }
        };
    }

    private function createRedirectMiddleware(): Redirect
    {
        return new Redirect(new Psr17Factory(), $this->createUrlGenerator());
    }
}
