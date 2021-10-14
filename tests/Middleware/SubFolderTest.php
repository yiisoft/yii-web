<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\Web\Exception\BadUriPrefixException;
use Yiisoft\Yii\Web\Middleware\SubFolder;

class SubFolderTest extends TestCase
{
    protected string $urlGeneratorUriPrefix;
    protected Aliases $aliases;
    protected ?ServerRequestInterface $lastRequest;

    public function setUp(): void
    {
        $this->urlGeneratorUriPrefix = '';
        $this->lastRequest = null;
        $this->aliases = new Aliases(['@baseUrl' => '/default/web']);
    }

    public function testDefault(): void
    {
        $request = $this->createRequest($uri = '/', $script = '/index.php');
        $mw = $this->createMiddleware(null, ['@baseUrl']);

        $this->process($mw, $request);

        $this->assertEquals('/default/web', $this->aliases->get('@baseUrl'));
        $this->assertEquals('', $this->urlGeneratorUriPrefix);
        $this->assertEquals($uri, $this->getRequestPath());
    }

    public function testSeveralAliases(): void
    {
        $request = $this->createRequest('/custom_public/index.php?test', '/index.php');
        $middleware = $this->createMiddleware('/custom_public', ['@a', '@b']);

        $this->process($middleware, $request);

        $this->assertEquals('/custom_public', $this->aliases->get('@a'));
        $this->assertEquals('/custom_public', $this->aliases->get('@b'));
    }

    public function testCustomPrefix(): void
    {
        $request = $this->createRequest($uri = '/custom_public/index.php?test', $script = '/index.php');
        $mw = $this->createMiddleware('/custom_public', ['@baseUrl']);

        $this->process($mw, $request);

        $this->assertEquals('/custom_public', $this->aliases->get('@baseUrl'));
        $this->assertEquals('/custom_public', $this->urlGeneratorUriPrefix);
        $this->assertEquals('/index.php', $this->getRequestPath());
    }

    public function testAutoPrefix(): void
    {
        $request = $this->createRequest($uri = '/public/', $script = '/public/index.php');
        $mw = $this->createMiddleware(null, ['@baseUrl']);

        $this->process($mw, $request);

        $this->assertEquals('/public', $this->aliases->get('@baseUrl'));
        $this->assertEquals('/public', $this->urlGeneratorUriPrefix);
        $this->assertEquals('/', $this->getRequestPath());
    }

    public function testAutoPrefixLogn(): void
    {
        $prefix = '/root/php/dev-server/project-42/index_html/public/web';
        $uri = "{$prefix}/";
        $script = "{$prefix}/index.php";
        $request = $this->createRequest($uri, $script);
        $mw = $this->createMiddleware(null, ['@baseUrl']);

        $this->process($mw, $request);

        $this->assertEquals($prefix, $this->aliases->get('@baseUrl'));
        $this->assertEquals($prefix, $this->urlGeneratorUriPrefix);
        $this->assertEquals('/', $this->getRequestPath());
    }

    public function testAutoPrefixAndUriWithoutTrailingSlash(): void
    {
        $request = $this->createRequest($uri = '/public', $script = '/public/index.php');
        $mw = $this->createMiddleware(null, ['@baseUrl']);

        $this->process($mw, $request);

        $this->assertEquals('/public', $this->aliases->get('@baseUrl'));
        $this->assertEquals('/public', $this->urlGeneratorUriPrefix);
        $this->assertEquals('/', $this->getRequestPath());
    }

    public function testAutoPrefixFullUrl(): void
    {
        $request = $this->createRequest($uri = '/public/index.php?test', $script = '/public/index.php');
        $mw = $this->createMiddleware(null, ['@baseUrl']);

        $this->process($mw, $request);

        $this->assertEquals('/public', $this->aliases->get('@baseUrl'));
        $this->assertEquals('/public', $this->urlGeneratorUriPrefix);
        $this->assertEquals('/index.php', $this->getRequestPath());
    }

    public function testFailedAutoPrefix(): void
    {
        $request = $this->createRequest($uri = '/web/index.php', $script = '/public/index.php');
        $mw = $this->createMiddleware(null, ['@baseUrl']);

        $this->process($mw, $request);

        $this->assertEquals('/default/web', $this->aliases->get('@baseUrl'));
        $this->assertEquals('', $this->urlGeneratorUriPrefix);
        $this->assertEquals($uri, $this->getRequestPath());
    }

    public function testCustomPrefixWithTrailingSlash(): void
    {
        $request = $this->createRequest($uri = '/web/', $script = '/public/index.php');
        $mw = $this->createMiddleware('/web/', ['@baseUrl']);

        $this->expectException(BadUriPrefixException::class);
        $this->expectExceptionMessage('Wrong URI prefix value');

        $this->process($mw, $request);
    }

    public function testCustomPrefixFromMiddleOfUri(): void
    {
        $request = $this->createRequest($uri = '/web/middle/public', $script = '/public/index.php');
        $mw = $this->createMiddleware('/middle', ['@baseUrl']);

        $this->expectException(BadUriPrefixException::class);
        $this->expectExceptionMessage('URI prefix does not match');

        $this->process($mw, $request);
    }

    public function testCustomPrefixDoesNotMatch(): void
    {
        $request = $this->createRequest($uri = '/web/', $script = '/public/index.php');
        $mw = $this->createMiddleware('/other_prefix', ['@baseUrl']);

        $this->expectException(BadUriPrefixException::class);
        $this->expectExceptionMessage('URI prefix does not match');

        $this->process($mw, $request);
    }

    public function testCustomPrefixDoesNotMatchCompletely(): void
    {
        $request = $this->createRequest($uri = '/project1/web/', $script = '/public/index.php');
        $mw = $this->createMiddleware('/project1/we', ['@baseUrl']);

        $this->expectException(BadUriPrefixException::class);
        $this->expectExceptionMessage('URI prefix does not match completely');

        $this->process($mw, $request);
    }

    public function testAutoPrefixDoesNotMatchCompletely(): void
    {
        $request = $this->createRequest($uri = '/public/web/', $script = '/pub/index.php');
        $mw = $this->createMiddleware(null, ['@baseUrl']);

        $this->process($mw, $request);

        $this->assertEquals('/default/web', $this->aliases->get('@baseUrl'));
        $this->assertEquals('', $this->urlGeneratorUriPrefix);
        $this->assertEquals($uri, $this->getRequestPath());
    }

    private function process(SubFolder $middleware, ServerRequestInterface $request): ResponseInterface
    {
        $handler = new class () implements RequestHandlerInterface {
            public ?ServerRequestInterface $request = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;
                return new Response();
            }
        };
        $this->lastRequest = &$handler->request;
        return $middleware->process($request, $handler);
    }

    private function getRequestPath(): string
    {
        return $this->lastRequest->getUri()->getPath();
    }

    private function createMiddleware(?string $prefix, array $setAliases): SubFolder
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('setUriPrefix')->willReturnCallback(function ($prefix) {
            $this->urlGeneratorUriPrefix = $prefix;
        });
        $urlGenerator->method('getUriPrefix')->willReturnReference($this->urlGeneratorUriPrefix);

        return new SubFolder($urlGenerator, $this->aliases, $prefix, $setAliases);
    }

    private function createRequest(string $uri = '/', string $scriptPath = '/'): ServerRequestInterface
    {
        return new ServerRequest('get', $uri, [], null, '1.1', ['SCRIPT_NAME' => $scriptPath]);
    }
}
