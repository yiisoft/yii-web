<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Response;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Middleware\ActionCaller;
use PHPUnit\Framework\TestCase;

class ActionCallerTest extends TestCase
{
    public function testProcess()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($this);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $caller = new ActionCaller('this', 'exampleMethod', $container);

        $response = $caller->process($request, $handler);
        self::assertEquals(204, $response->getStatusCode());
    }

    public function exampleMethod(): ResponseInterface
    {
        return new Response(204);
    }
}
