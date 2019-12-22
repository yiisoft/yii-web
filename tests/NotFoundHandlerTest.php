<?php

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\Method;
use Yiisoft\Yii\Web\NotFoundHandler;

final class NotFoundHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function shouldReturnCode404(): void
    {
        $response = $this->createHandler()->handle($this->createRequest());
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shouldReturnCorrectErrorInBody(): void
    {
        $response = $this->createHandler()->handle($this->createRequest('http://site.com/test/path?param=1'));
        $this->assertEquals('We were unable to find the page /test/path.', (string)$response->getBody());
    }

    private function createHandler(): NotFoundHandler
    {
        return new NotFoundHandler(new Psr17Factory());
    }

    private function createRequest(string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest(Method::GET, $uri);
    }
}
