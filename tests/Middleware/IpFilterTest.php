<?php


namespace Yiisoft\Yii\Web\Tests\Middleware;

use Http\Message\ResponseFactory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Middleware\IpFilter;
use PHPUnit_Framework_MockObject_MockObject;

class IpFilterTest extends TestCase
{
    const REQUEST_PARAMS = [
        'REMOTE_ADDR' => '8.8.8.8',
    ];

    const ALLOWED_IP = '1.1.1.1';

    /**
     * @var ResponseFactory|PHPUnit_Framework_MockObject_MockObject
     */
    private $responseFactoryMock;

    /**
     * @var RequestHandlerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $requestHandlerMock;

    private $ipFilter;

    public function setUp()
    {
        parent::setUp();
        $this->responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
        $this->requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->ipFilter = new IpFilter(self::ALLOWED_IP, $this->responseFactoryMock);
    }

    /**
     * @test
     */
    public function processReturnsAccessDeniedResponseWhenIpIsNotAllowed()
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

    /**
     * @test
     */
    public function processCallsRequestHandlerWhenRemoteAddressIsAllowed()
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

    public function setUpResponseFactory()
    {
        $response = new Response(403);
        $this->responseFactoryMock
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response);
    }
}
