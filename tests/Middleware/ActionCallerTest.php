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
    /** @var ServerRequestInterface  */
    private $request;

    /** @var RequestHandlerInterface  */
    private $handler;

    protected function setUp()
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    public function testProcess()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($this);

        $caller = new ActionCaller('this', 'process', $container);

        $response = $caller->process($this->request, $this->handler);
        self::assertEquals(204, $response->getStatusCode());
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->assertSame($this->request, $request);
        $this->assertSame($this->handler, $handler);

        return new Response(204);
    }
}
