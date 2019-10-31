<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Middleware\Chain;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\Tests\Middleware\Mock\MockMiddleware;

class ChainTest extends TestCase
{
    public function testProcess()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $middleware1 = new MockMiddleware();

        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $middleware2->expects($this->once())->method('process');

        $chain = new Chain($middleware1, $middleware2);
        $chain->process($request, $handler);
    }
}
