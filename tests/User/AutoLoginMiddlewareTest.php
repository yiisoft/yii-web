<?php

namespace Yiisoft\Yii\Web\Tests\User;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Yii\Web\User\User;
use Yiisoft\Yii\Web\User\AutoLoginMiddleware;

class AutoLoginMiddlewareTest extends TestCase
{
    /**
     * @var RequestHandlerInterface
     */
    private $requestHandlerMock;

    /**
     * @var ServerRequestInterface
     */
    private $requestMock;

    /**
     * @var AutoLoginMiddleware
     */
    private $autoLoginMiddlewareMock;

    /**
     * @var IdentityRepositoryInterface
     */
    private $identityRepositoryInterfaceMock;

    /**
     * @var IdentityInterface
     */
    private $identityInterfaceMock;
    /**
     * @var User
     */
    private $userMock;

    public function testProcessOK(): void
    {
        $this->mockDataRequest();
        $this->mockDataCookie(["remember" => json_encode(['1', '123456', 60])]);
        $this->mockFindIdentity();

        $this->userMock
            ->expects($this->once())
            ->method('login')
            ->willReturn(true);

        $response = new Response();
        $this->requestHandlerMock
            ->expects($this->once())
            ->method('handle')
            ->willReturn($response);


        $this->assertEquals($this->autoLoginMiddlewareMock->process($this->requestMock, $this->requestHandlerMock), $response);
    }

    public function testProcessErrorLogin(): void
    {
        $this->mockDataRequest();
        $this->mockDataCookie(["remember" => json_encode(['1', '123456', 60])]);
        $this->mockFindIdentity();

        $this->userMock
            ->expects($this->once())
            ->method('login')
            ->willReturn(false);

        $this->expectException(\Exception::class);
        $this->autoLoginMiddlewareMock->process($this->requestMock, $this->requestHandlerMock);
    }

    public function testProcessCookieEmpty(): void
    {
        $this->mockDataRequest();
        $this->mockDataCookie([]);
        $this->mockFindIdentity();

        $this->expectException(\Exception::class);
        $this->autoLoginMiddlewareMock->process($this->requestMock, $this->requestHandlerMock);
    }

    public function testProcessCookieWithInvalidParams(): void
    {
        $this->mockDataRequest();
        $this->mockDataCookie(["remember" => json_encode(['1', '123456', 60, "paramInvalid"])]);
        $this->mockFindIdentity();

        $this->expectException(\Exception::class);
        $this->autoLoginMiddlewareMock->process($this->requestMock, $this->requestHandlerMock);
    }

    private function mockDataRequest(): void
    {
        $this->requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->userMock = $this->createMock(User::class);
        $this->identityInterfaceMock = $this->createMock(IdentityInterface::class);
        $this->identityRepositoryInterfaceMock = $this->createMock(IdentityRepositoryInterface::class);
        $this->autoLoginMiddlewareMock = new AutoLoginMiddleware($this->userMock, $this->identityRepositoryInterfaceMock);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
    }

    private function mockDataCookie(array $cookie): void
    {
        $this->requestMock
            ->expects($this->any())
            ->method('getCookieParams')
            ->willReturn($cookie);
    }

    private function mockFindIdentity(): void
    {
        $this->identityRepositoryInterfaceMock
            ->expects($this->any())
            ->method('findIdentity')
            ->willReturn($this->identityInterfaceMock);
    }
}
