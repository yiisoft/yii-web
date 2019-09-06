<?php


namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\Method;
use Yiisoft\Security\Random;
use Yiisoft\Security\TokenMasker;
use Yiisoft\Yii\Web\Middleware\Csrf;
use Yiisoft\Yii\Web\Session\SessionInterface;

final class CsrfTest extends TestCase
{
    private const PARAM_NAME = 'csrf';

    /**
     * @test
     */
    public function validTokenInBodyPostRequestResultIn200()
    {
        $token      = $this->generateToken();
        $middleware = $this->createCsrfMiddlewareWithToken($token);
        $response   = $middleware->process($this->createPostServerRequestWithBodyToken($token), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function validTokenInBodyPutRequestResultIn200()
    {
        $token      = $this->generateToken();
        $middleware = $this->createCsrfMiddlewareWithToken($token);
        $response   = $middleware->process($this->createPutServerRequestWithBodyToken($token), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function validTokenInBodyDeleteRequestResultIn200()
    {
        $token      = $this->generateToken();
        $middleware = $this->createCsrfMiddlewareWithToken($token);
        $response   = $middleware->process($this->createDeleteServerRequestWithBodyToken($token), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function validTokenInHeaderResultIn200()
    {
        $token      = $this->generateToken();
        $middleware = $this->createCsrfMiddlewareWithToken($token);
        $response   = $middleware->process($this->createPostServerRequestWithHeaderToken($token), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function getIsAlwaysAllowed()
    {
        $middleware = $this->createCsrfMiddlewareWithToken('');
        $response   = $middleware->process($this->createServerRequest(Method::GET), $this->createRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function invalidTokenResultIn400()
    {
        $middleware = $this->createCsrfMiddlewareWithToken($this->generateToken());
        $response   = $middleware->process($this->createPostServerRequestWithBodyToken($this->generateToken()), $this->createRequestHandler());
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function emptyTokenInSessionResultIn400()
    {
        $middleware = $this->createCsrfMiddlewareWithToken('');
        $response   = $middleware->process($this->createPostServerRequestWithBodyToken($this->generateToken()), $this->createRequestHandler());
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function emptyTokenInRequestResultIn400()
    {
        $middleware = $this->createCsrfMiddlewareWithToken($this->generateToken());
        $response   = $middleware->process($this->createServerRequest(), $this->createRequestHandler());
        $this->assertEquals(400, $response->getStatusCode());
    }


    private function createServerRequest(string $method = Method::POST, array $bodyParams = [], array $headParams = []): ServerRequestInterface
    {
        $request = new ServerRequest($method, '/', $headParams);

        return $request->withParsedBody($bodyParams);
    }

    private function createPostServerRequestWithBodyToken(string $token): ServerRequestInterface
    {
        return $this->createServerRequest(Method::POST, $this->getBodyRequestParamsByToken($token));
    }

    private function createPutServerRequestWithBodyToken(string $token): ServerRequestInterface
    {
        return $this->createServerRequest(Method::PUT, $this->getBodyRequestParamsByToken($token));
    }

    private function createDeleteServerRequestWithBodyToken(string $token): ServerRequestInterface
    {
        return $this->createServerRequest(Method::DELETE, $this->getBodyRequestParamsByToken($token));
    }

    private function createPostServerRequestWithHeaderToken(string $token): ServerRequestInterface
    {
        return $this->createServerRequest(Method::POST, [], [
            Csrf::HEADER_NAME => TokenMasker::mask($token),
        ]);
    }

    private function createRequestHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };
    }

    private function createSessionMock(string $returnToken)
    {
        /**
         * @var SessionInterface|MockObject $sessionMock
         */
        $sessionMock = $this->createMock(SessionInterface::class);

        $sessionMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($returnToken);

        return $sessionMock;
    }


    private function createCsrfMiddlewareWithToken(string $token): Csrf
    {
        $middleware = new Csrf(new Psr17Factory(), $this->createSessionMock($token));
        $middleware->setName(self::PARAM_NAME);

        return $middleware;
    }

    private function generateToken(): string
    {
        return Random::string();
    }

    private function getBodyRequestParamsByToken(string $token): array
    {
        return [
            self::PARAM_NAME => TokenMasker::mask($token),
        ];
    }
}
