<?php

namespace Yiisoft\Yii\Web\Middleware;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WebActionsCallerTest extends TestCase
{
    /** @var ServerRequestInterface  */
    private $request;

    /** @var RequestHandlerInterface  */
    private $handler;

    /** @var ContainerInterface  */
    private $container;

    protected function setUp()
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('get')->willReturn($this);
    }

    public function testProcess()
    {
        $this->request
            ->method('getAttribute')
            ->with($this->equalTo('action'))
            ->willReturn('example');

        $response = (new WebActionsCaller('this', $this->container))->process($this->request, $this->handler);
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testExceptionOnNullAction()
    {
        $this->request
            ->method('getAttribute')
            ->with($this->equalTo('action'))
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        (new WebActionsCaller('this', $this->container))->process($this->request, $this->handler);
    }

    public function testHandlerInvocation()
    {
        $this->request
            ->method('getAttribute')
            ->with($this->equalTo('action'))
            ->willReturn('notExistant');

        $this->handler
            ->expects($this->once())
            ->method('handle');

        (new WebActionsCaller('this', $this->container))->process($this->request, $this->handler);
    }

    public function example(): ResponseInterface
    {
        return new Response(204);
    }
}
