<?php

namespace Yiisoft\Yii\Web\Tests\User;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Log\Logger;
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
     * @var Logger
     */
    private $loggerMock;

    /**
     * @var User
     */
    private $userMock;

    public function testProcessOK(): void
    {
        $this->mockDataRequest();
        $this->mockDataCookie(["remember" => json_encode(['1', 'ABCD1234', 60])]);
        $this->mockFindIdentity();

        $this->userMock
            ->expects($this->once())
            ->method('validateAuthKey')
            ->willReturn(true);

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
        $this->mockDataCookie(["remember" => json_encode(['1', 'ABCD1234', 60])]);
        $this->mockFindIdentity();

        $this->userMock
            ->expects($this->once())
            ->method('validateAuthKey')
            ->willReturn(true);

        $this->userMock
            ->expects($this->once())
            ->method('login')
            ->willReturn(false);

        $memory = memory_get_usage();
        $this->loggerMock->setTraceLevel(3);

        $this->autoLoginMiddlewareMock->process($this->requestMock, $this->requestHandlerMock);

        $messages = $this->getInaccessibleProperty($this->loggerMock, 'messages');
        $this->assertEquals($messages[0][1], 'Unable to authenticate used by cookie.');
    }

    public function testProcessInvalidAuthKey(): void
    {
        $this->mockDataRequest();
        $this->mockDataCookie(["remember" => json_encode(['1', '123456', 60])]);
        $this->mockFindIdentity();

        $memory = memory_get_usage();
        $this->loggerMock->setTraceLevel(3);

        $this->autoLoginMiddlewareMock->process($this->requestMock, $this->requestHandlerMock);

        $messages = $this->getInaccessibleProperty($this->loggerMock, 'messages');
        $this->assertEquals($messages[0][1], 'Unable to authenticate used by cookie. Invalid auth key.');
    }

    public function testProcessCookieEmpty(): void
    {
        $this->mockDataRequest();
        $this->mockDataCookie([]);
        $this->mockFindIdentity();

        $memory = memory_get_usage();
        $this->loggerMock->setTraceLevel(3);

        $this->autoLoginMiddlewareMock->process($this->requestMock, $this->requestHandlerMock);

        $messages = $this->getInaccessibleProperty($this->loggerMock, 'messages');
        $this->assertEquals($messages[0][1], 'Unable to authenticate used by cookie.');
    }

    public function testProcessCookieWithInvalidParams(): void
    {
        $this->mockDataRequest();
        $this->mockDataCookie(["remember" => json_encode(['1', '123456', 60, "paramInvalid"])]);
        $this->mockFindIdentity();

        $memory = memory_get_usage();
        $this->loggerMock->setTraceLevel(3);

        $this->autoLoginMiddlewareMock->process($this->requestMock, $this->requestHandlerMock);

        $messages = $this->getInaccessibleProperty($this->loggerMock, 'messages');
        $this->assertEquals($messages[0][1], 'Unable to authenticate used by cookie.');
    }

    private function mockDataRequest(): void
    {
        $this->requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->userMock = $this->createMock(User::class);
        $this->identityInterfaceMock = $this->createMock(IdentityInterface::class);

        $this->loggerMock = $this->getMockBuilder(Logger::class)
            ->onlyMethods(['dispatch'])
            ->getMock();

        $this->identityRepositoryInterfaceMock = $this->createMock(IdentityRepositoryInterface::class);
        $this->autoLoginMiddlewareMock = new AutoLoginMiddleware($this->userMock, $this->identityRepositoryInterfaceMock, $this->loggerMock);
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

    /**
     * Gets an inaccessible object property.
     * @param $object
     * @param $propertyName
     * @param bool $revoke whether to make property inaccessible after getting
     * @return mixed
     * @throws \ReflectionException
     */
    protected function getInaccessibleProperty($object, $propertyName, bool $revoke = true)
    {
        $class = new \ReflectionClass($object);
        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $result = $property->getValue($object);
        if ($revoke) {
            $property->setAccessible(false);
        }
        return $result;
    }
}
