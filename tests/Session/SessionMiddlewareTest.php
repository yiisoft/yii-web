<?php


namespace Yiisoft\Yii\Web\Tests\Session;

use Psr\Http\Message\ResponseInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Cache\Tests\TestCase;
use Yiisoft\Yii\Web\Session\SessionException;
use Yiisoft\Yii\Web\Session\SessionInterface;
use Yiisoft\Yii\Web\Session\SessionMiddleware;

class SessionMiddlewareTest extends TestCase
{
    const COOKIE_PARAMETERS = array (
        'path' => 'examplePath',
        'domain' => 'exampleDomain',
        'httponly' => 'httponly',
        'samesite' => 'Strict',
        'lifetime' => 3600,
        'secure' => true,
    );

    const CURRENT_SID = 'exampleCurrentSidValue';
    const REQUEST_SID = 'exampleRequestSidValue';
    const SESSION_NAME = 'exampleSessionName';

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

    public function setUp()
    {
        parent::setUp();
        $this->requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->sessionMock = $this->createMock(SessionInterface::class);
        $this->sessionMiddleware = new SessionMiddleware($this->sessionMock);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->uriMock = $this->createMock(UriInterface::class);
    }

    /**
     * @test
     */
    public function processDiscardsSessionWhenRequestHandlerThrowsException()
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

    /**
     * @test
     */
    public function processThrowsSessionExceptionWhenConnectionIsNotUsingHttps()
    {
        $this->setUpSessionMock();
        $this->setUpRequestMock(false);
        $this->expectException(SessionException::class);
        $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);
    }

    /**
     * @test
     */
    public function processGetsDomainFromRequestWhenDomainCookieParameterNotProvided()
    {
        $this->setUpSessionMock(false);
        $this->setUpRequestMock();

        $this->uriMock
            ->expects($this->once())
            ->method('getHost')
            ->willReturn('domain');

        $this->setUpRequestHandlerMock();
        $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);
    }

    private function setUpRequestHandlerMock()
    {
        $responseMock = $this->getResponseMock();
        $this->requestHandlerMock
            ->expects($this->once())
            ->method('handle')
            ->willReturn($responseMock);
    }

    private function getResponseMock()
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock
            ->expects($this->any())
            ->method('withAddedHeader')
            ->willReturn($responseMock);
        return $responseMock;
    }

    private function setUpSessionMock(bool $cookieDomainProvided = true, bool $isActive = true)
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

    private function setUpRequestMock(bool $isConnectionSecure = true)
    {
        $uriScheme = $isConnectionSecure ? 'https' : 'http';
        $this->setUpUriMock($uriScheme);

        $this->requestMock
            ->expects($this->any())
            ->method('getUri')
            ->willReturn($this->uriMock);

        $cookieParams[self::SESSION_NAME] = self::CURRENT_SID;
        $this->requestMock
            ->expects($this->any())
            ->method('getCookieParams')
            ->willReturn(self::REQUEST_SID);
    }

    private function setUpUriMock(string $uriScheme)
    {
        $this->uriMock
            ->expects($this->any())
            ->method('getScheme')
            ->willReturn($uriScheme);
    }
}
