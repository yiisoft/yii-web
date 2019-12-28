<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
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
        $this->aliases = new Aliases(['@web' => '/default/web']);
    }

    public function testDefault(): void
    {
        $request = $this->createRequest($uri = '/', $script = '/index.php');
        $mw = $this->createMiddleware();

        $this->process($mw, $request);

        $this->assertEquals('/default/web', $this->aliases->get('@web'));
        $this->assertEquals('', $this->urlGeneratorUriPrefix);
        $this->assertEquals($uri, $this->getRequestPath());
    }

    public function testCustomPrefix(): void
    {
        $request = $this->createRequest($uri = '/custom_public/index.php?test', $script = '/index.php');
        $mw = $this->createMiddleware();
        $mw->prefix = '/custom_public';

        $this->process($mw, $request);

        $this->assertEquals('/custom_public', $this->aliases->get('@web'));
        $this->assertEquals('/custom_public', $this->urlGeneratorUriPrefix);
        $this->assertEquals('/index.php', $this->getRequestPath());
    }

    public function testAutoPrefix(): void
    {
        $request = $this->createRequest($uri = '/public/', $script = '/public/index.php');
        $mw = $this->createMiddleware();

        $this->process($mw, $request);

        $this->assertEquals('/public', $this->aliases->get('@web'));
        $this->assertEquals('/public', $this->urlGeneratorUriPrefix);
        $this->assertEquals('/', $this->getRequestPath());
    }

    public function testAutoPrefixLogn(): void
    {
        $prefix = '/root/php/dev-server/project-42/index_html/public/web';
        $uri = "{$prefix}/";
        $script = "{$prefix}/index.php";
        $request = $this->createRequest($uri, $script);
        $mw = $this->createMiddleware();

        $this->process($mw, $request);

        $this->assertEquals($prefix, $this->aliases->get('@web'));
        $this->assertEquals($prefix, $this->urlGeneratorUriPrefix);
        $this->assertEquals('/', $this->getRequestPath());
    }

    public function testAutoPrefixAndUriWithoutTrailingSlash(): void
    {
        $request = $this->createRequest($uri = '/public', $script = '/public/index.php');
        $mw = $this->createMiddleware();

        $this->process($mw, $request);

        $this->assertEquals('/public', $this->aliases->get('@web'));
        $this->assertEquals('/public', $this->urlGeneratorUriPrefix);
        $this->assertEquals('/', $this->getRequestPath());
    }

    public function testAutoPrefixFullUrl(): void
    {
        $request = $this->createRequest($uri = '/public/index.php?test', $script = '/public/index.php');
        $mw = $this->createMiddleware();

        $this->process($mw, $request);

        $this->assertEquals('/public', $this->aliases->get('@web'));
        $this->assertEquals('/public', $this->urlGeneratorUriPrefix);
        $this->assertEquals('/index.php', $this->getRequestPath());
    }

    public function testFailedAutoPrefix(): void
    {
        $request = $this->createRequest($uri = '/web/index.php', $script = '/public/index.php');
        $mw = $this->createMiddleware();

        $this->process($mw, $request);

        $this->assertEquals('/default/web', $this->aliases->get('@web'));
        $this->assertEquals('', $this->urlGeneratorUriPrefix);
        $this->assertEquals($uri, $this->getRequestPath());
    }

    public function testCustomPrefixWithTrailingSlash(): void
    {
        $request = $this->createRequest($uri = '/web/', $script = '/public/index.php');
        $mw = $this->createMiddleware();
        $mw->prefix = '/web/';

        $this->expectException(BadUriPrefixException::class);
        $this->expectExceptionMessage('Wrong URI prefix value');

        $this->process($mw, $request);
    }

    public function testCustomPrefixFromMiddleOfUri(): void
    {
        $request = $this->createRequest($uri = '/web/middle/public', $script = '/public/index.php');
        $mw = $this->createMiddleware();
        $mw->prefix = '/middle';

        $this->expectException(BadUriPrefixException::class);
        $this->expectExceptionMessage('URI prefix does not match');

        $this->process($mw, $request);
    }

    public function testCustomPrefixDoesNotMatch(): void
    {
        $request = $this->createRequest($uri = '/web/', $script = '/public/index.php');
        $mw = $this->createMiddleware();
        $mw->prefix = '/other_prefix';

        $this->expectException(BadUriPrefixException::class);
        $this->expectExceptionMessage('URI prefix does not match');

        $this->process($mw, $request);
    }

    public function testCustomPrefixDoesNotMatchCompletely(): void
    {
        $request = $this->createRequest($uri = '/project1/web/', $script = '/public/index.php');
        $mw = $this->createMiddleware();
        $mw->prefix = '/project1/we';

        $this->expectException(BadUriPrefixException::class);
        $this->expectExceptionMessage('URI prefix does not match completely');

        $this->process($mw, $request);
    }

    public function testAutoPrefixDoesNotMatchCompletely(): void
    {
        $request = $this->createRequest($uri = '/public/web/', $script = '/pub/index.php');
        $mw = $this->createMiddleware();

        $this->process($mw, $request);

        $this->assertEquals('/default/web', $this->aliases->get('@web'));
        $this->assertEquals('', $this->urlGeneratorUriPrefix);
        $this->assertEquals($uri, $this->getRequestPath());
    }

    private function process(SubFolder $middleware, ServerRequestInterface $request): ResponseInterface
    {
        $handler = new class implements RequestHandlerInterface {
            public ?ServerRequestInterface $request = null;
            public function handle(ServerRequestInterface $request): ResponseInterface {
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

    private function createMiddleware(): SubFolder
    {
        // URL Generator
        /** @var MockObject|UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $this->getMockBuilder(UrlGeneratorInterface::class)->getMock();
        $urlGenerator->method('setUriPrefix')->willReturnCallback(function ($prefix) {
            $this->urlGeneratorUriPrefix = $prefix;
        });
        $urlGenerator->method('getUriPrefix')->willReturnReference($this->urlGeneratorUriPrefix);

        return new SubFolder($urlGenerator, $this->aliases);
    }

    private function createRequest(string $uri = '/', string $scriptPath = '/'): ServerRequestInterface {
        $request = new ServerRequest('get', $uri, [], null, '1.1', ['SCRIPT_NAME' => $scriptPath]);
        return $request;
    }
}
