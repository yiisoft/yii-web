<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Session;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Session\SessionException;
use Yiisoft\Yii\Web\Session\SessionInterface;
use Yiisoft\Yii\Web\Session\SessionMiddleware;

class SessionMiddlewareTest extends TestCase
{
    private const COOKIE_PARAMETERS = [
        'path' => 'examplePath',
        'domain' => 'exampleDomain',
        'httponly' => true,
        'samesite' => 'Strict',
        'lifetime' => 3600,
        'secure' => true,
    ];

    private const CURRENT_SID = 'exampleCurrentSidValue';
    private const REQUEST_SID = 'exampleRequestSidValue';
    private const SESSION_NAME = 'exampleSessionName';

    /**
     * @var RequestHandlerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $requestHandlerMock;

    /**
     * @var SessionInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionMock;

    /**
     * @var ServerRequestInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var UriInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $uriMock;

    /**
     * @var SessionMiddleware
     */
    private $sessionMiddleware;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->sessionMock = $this->createMock(SessionInterface::class);
        $this->sessionMiddleware = new SessionMiddleware($this->sessionMock);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->uriMock = $this->createMock(UriInterface::class);
    }

    public function testProcessDiscardsSessionWhenRequestHandlerThrowsException(): void
    {
        $this->requestHandlerMock
            ->expects($this->once())
            ->method('handle')
            ->willThrowException(new \Exception());

        $this->sessionMock
            ->expects($this->once())
            ->method('discard');

        $this->expectException(\Exception::class);
        $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);
    }

    public function testProcessThrowsSessionExceptionWhenConnectionIsNotUsingHttps(): void
    {
        $this->setUpSessionMock();
        $this->setUpRequestMock(false);
        $this->expectException(SessionException::class);
        $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);
    }

    public function testProcessGetsDomainFromRequestWhenDomainCookieParameterNotProvided(): void
    {
        $this->setUpSessionMock(false);
        $this->setUpRequestMock();

        $this->uriMock
            ->expects($this->once())
            ->method('getHost')
            ->willReturn('domain');

        $response = new Response();
        $this->setUpRequestHandlerMock($response);
        $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);
    }

    public function testProcessDoesNotAlterResponseIfSessionIsNotActive(): void
    {
        $this->setUpSessionMock(true, false);
        $this->setUpRequestMock();

        $response = new Response();
        $this->setUpRequestHandlerMock($response);

        $result = $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);
        $this->assertEquals($response, $result);
    }

    private function setUpRequestHandlerMock(ResponseInterface $response): void
    {
        $this->requestHandlerMock
            ->expects($this->once())
            ->method('handle')
            ->willReturn($response);
    }

    private function setUpSessionMock(bool $cookieDomainProvided = true, bool $isActive = true): void
    {
        $this->sessionMock
            ->expects($this->any())
            ->method('isActive')
            ->willReturn($isActive);

        $this->sessionMock
            ->expects($this->any())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $this->sessionMock
            ->expects($this->any())
            ->method('getID')
            ->willReturn(self::CURRENT_SID);

        $cookieParams = self::COOKIE_PARAMETERS;
        if (!$cookieDomainProvided) {
            $cookieParams['domain'] = '';
        }

        $this->sessionMock
            ->expects($this->any())
            ->method('getCookieParameters')
            ->willReturn($cookieParams);
    }

    private function setUpRequestMock(bool $isConnectionSecure = true): void
    {
        $uriScheme = $isConnectionSecure ? 'https' : 'http';
        $this->setUpUriMock($uriScheme);

        $this->requestMock
            ->expects($this->any())
            ->method('getUri')
            ->willReturn($this->uriMock);

        $requestCookieParams = [
            self::SESSION_NAME => self::REQUEST_SID,
        ];
        $this->requestMock
            ->expects($this->any())
            ->method('getCookieParams')
            ->willReturn($requestCookieParams);
    }

    private function setUpUriMock(string $uriScheme): void
    {
        $this->uriMock
            ->expects($this->any())
            ->method('getScheme')
            ->willReturn($uriScheme);
    }
}
