<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Di\Container;
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
        $container = new Container([self::class => $this]);

        $caller = new ActionCaller(self::class, 'process', $container);

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
