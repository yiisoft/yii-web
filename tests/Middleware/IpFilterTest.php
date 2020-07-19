<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Http\Message\ResponseFactory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Validator\Rule\Ip;
use Yiisoft\Yii\Web\Middleware\IpFilter;

class IpFilterTest extends TestCase
{
    private const REQUEST_PARAMS = [
        'REMOTE_ADDR' => '8.8.8.8',
    ];

    private const ALLOWED_IP = '1.1.1.1';

    /**
     * @var ResponseFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $responseFactoryMock;

    /**
     * @var RequestHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestHandlerMock;

    private IpFilter $ipFilter;

    public function setUp(): void
    {
        parent::setUp();
        $this->responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
        $this->requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->ipFilter = new IpFilter((new Ip())->ranges([self::ALLOWED_IP]), $this->responseFactoryMock);
    }

    public function testProcessReturnsAccessDeniedResponseWhenIpIsNotAllowed(): void
    {
        $this->setUpResponseFactory();
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock
            ->expects($this->once())
            ->method('getServerParams')
            ->willReturn(self::REQUEST_PARAMS);

        $this->requestHandlerMock
            ->expects($this->never())
            ->method('handle')
            ->with($requestMock);

        $response = $this->ipFilter->process($requestMock, $this->requestHandlerMock);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testProcessCallsRequestHandlerWhenRemoteAddressIsAllowed(): void
    {
        $requestParams = [
            'REMOTE_ADDR' => self::ALLOWED_IP,
        ];
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock
            ->expects($this->once())
            ->method('getServerParams')
            ->willReturn($requestParams);

        $this->requestHandlerMock
            ->expects($this->once())
            ->method('handle')
            ->with($requestMock);

        $this->ipFilter->process($requestMock, $this->requestHandlerMock);
    }

    public function setUpResponseFactory(): void
    {
        $response = new Response(403);
        $this->responseFactoryMock
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response);
    }
}
