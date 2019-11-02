<?php

namespace Yiisoft\Yii\Web\Tests\Auth;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Auth\AuthInterface;
use Yiisoft\Yii\Web\Auth\AuthMiddleware;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\User\IdentityInterface;

class AuthMiddlewareTest extends TestCase
{
    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var AuthInterface|MockObject */
    private $authenticator;

    protected function setUp()
    {
        $this->responseFactory = new Psr17Factory();
        $this->authenticator = $this->createMock(AuthInterface::class);
    }

    /**
     * @test
     */
    public function shouldAuthenticateAndSetAttribute()
    {
        $request = new ServerRequest('GET', '/');
        $identity = $this->createMock(IdentityInterface::class);
        $identityAttribute = 'identity';

        $this->authenticator
            ->expects($this->once())
            ->method('authenticate')
            ->willReturn($identity);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (ServerRequestInterface $request) use ($identityAttribute, $identity) {
                    $this->assertEquals($identity, $request->getAttribute($identityAttribute));

                    return $this->responseFactory->createResponse();
                }
            );

        $auth = new AuthMiddleware($this->responseFactory, $this->authenticator);
        $auth->setRequestName($identityAttribute);
        $auth->process($request, $handler);
    }

    /**
     * @test
     */
    public function shouldAuthenticateOptionalPath()
    {
        $path = '/optional';
        $request = new ServerRequest('GET', $path);

        $this->authenticator
            ->expects($this->once())
            ->method('authenticate')
            ->willReturn(null);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle');

        $auth = new AuthMiddleware($this->responseFactory, $this->authenticator);
        $auth->setOptional([$path]);
        $auth->process($request, $handler);
    }

    /**
     * @test
     */
    public function shouldNotAuthenticate()
    {
        $request = new ServerRequest('GET', '/');
        $header = 'Authenticated';
        $headerValue = 'false';

        $this->authenticator
            ->expects($this->once())
            ->method('authenticate')
            ->willReturn(null);

        $this->authenticator
            ->expects($this->once())
            ->method('challenge')
            ->willReturnCallback(
                function (ResponseInterface $response) use ($header, $headerValue) {
                    return $response->withHeader($header, $headerValue);
                }
            );

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $auth = new AuthMiddleware($this->responseFactory, $this->authenticator);
        $response = $auth->process($request, $handler);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals($headerValue, $response->getHeaderLine($header));
    }
}
