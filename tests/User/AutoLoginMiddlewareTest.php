<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\User;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Log\Logger;
use Yiisoft\Yii\Web\User\AutoLogin;
use Yiisoft\Yii\Web\User\AutoLoginMiddleware;
use Yiisoft\Yii\Web\User\User;

final class AutoLoginMiddlewareTest extends TestCase
{
    private Logger $logger;

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(Logger::class)
            ->onlyMethods(['dispatch'])
            ->getMock();
    }

    private function getLastLogMessage(): ?string
    {
        $messages = $this->getInaccessibleProperty($this->logger, 'messages');
        return $messages[0][1] ?? null;
    }

    public function testCorrectLogin(): void
    {
        $user = $this->getUserWithLoginExpected();

        $autoLogin = $this->getAutoLogin();
        $middleware = new AutoLoginMiddleware(
            $user,
            $this->getAutoLoginIdentityRepository(),
            $this->logger,
            $autoLogin
        );
        $request = $this->getRequestWithAutoLoginCookie(AutoLoginIdentity::ID, AutoLoginIdentity::KEY_CORRECT);

        $middleware->process($request, $this->getRequestHandler());

        $this->assertNull($this->getLastLogMessage());
    }

    public function testInvalidKey(): void
    {
        $user = $this->getUserWithoutLoginExpected();

        $autoLogin = $this->getAutoLogin();
        $middleware = new AutoLoginMiddleware(
            $user,
            $this->getAutoLoginIdentityRepository(),
            $this->logger,
            $autoLogin
        );
        $request = $this->getRequestWithAutoLoginCookie(AutoLoginIdentity::ID, AutoLoginIdentity::KEY_INCORRECT);

        $middleware->process($request, $this->getRequestHandler());

        $this->assertSame('Unable to authenticate user by cookie. Invalid key.', $this->getLastLogMessage());
    }

    public function testNoCookie(): void
    {
        $user = $this->getUserWithoutLoginExpected();

        $autoLogin = $this->getAutoLogin();
        $middleware = new AutoLoginMiddleware(
            $user,
            $this->getAutoLoginIdentityRepository(),
            $this->logger,
            $autoLogin
        );
        $request = $this->getRequestWithCookies([]);

        $middleware->process($request, $this->getRequestHandler());

        $this->assertNull($this->getLastLogMessage());
    }

    public function testEmptyCookie(): void
    {
        $user = $this->getUserWithoutLoginExpected();

        $autoLogin = $this->getAutoLogin();
        $middleware = new AutoLoginMiddleware(
            $user,
            $this->getAutoLoginIdentityRepository(),
            $this->logger,
            $autoLogin
        );
        $request = $this->getRequestWithCookies(['autoLogin' => '']);

        $middleware->process($request, $this->getRequestHandler());

        $this->assertSame('Unable to authenticate user by cookie. Invalid cookie.', $this->getLastLogMessage());
    }

    public function testInvalidCookie(): void
    {
        $user = $this->getUserWithoutLoginExpected();

        $autoLogin = $this->getAutoLogin();
        $middleware = new AutoLoginMiddleware(
            $user,
            $this->getAutoLoginIdentityRepository(),
            $this->logger,
            $autoLogin
        );
        $request = $this->getRequestWithCookies(
            [
                'autoLogin' => json_encode([AutoLoginIdentity::ID, AutoLoginIdentity::KEY_CORRECT, 'weird stuff']),
            ]
        );

        $middleware->process($request, $this->getRequestHandler());

        $this->assertSame('Unable to authenticate user by cookie. Invalid cookie.', $this->getLastLogMessage());
    }

    public function testIncorrectIdentity(): void
    {
        $user = $this->getUserWithoutLoginExpected();

        $middleware = new AutoLoginMiddleware(
            $user,
            $this->getIncorrectIdentityRepository(),
            $this->logger,
            $this->getAutoLogin()
        );

        $request = $this->getRequestWithAutoLoginCookie(AutoLoginIdentity::ID, AutoLoginIdentity::KEY_CORRECT);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Identity repository must return an instance of \Yiisoft\Yii\Web\User\AutoLoginIdentityInterface in order for auto-login to function.');

        $middleware->process($request, $this->getRequestHandlerThatIsNotCalled());
    }

    public function testIdentityNotFound(): void
    {
        $user = $this->getUserWithoutLoginExpected();

        $middleware = new AutoLoginMiddleware(
            $user,
            $this->getEmptyIdentityRepository(),
            $this->logger,
            $this->getAutoLogin()
        );

        $identityId = AutoLoginIdentity::ID;
        $request = $this->getRequestWithAutoLoginCookie($identityId, AutoLoginIdentity::KEY_CORRECT);

        $middleware->process($request, $this->getRequestHandler());

        $this->assertSame("Unable to authenticate user by cookie. Identity \"$identityId\" not found.", $this->getLastLogMessage());
    }

    public function testAddCookieAfterLogin()
    {
        $user = $this->getUserForSuccessfulAutologin();
        $autoLogin = $this->getAutoLogin();
        $middleware = new AutoLoginMiddleware(
            $user,
            $this->getAutoLoginIdentityRepository(),
            $this->logger,
            $autoLogin
        );
        $request = $this->getRequestWithAutoLoginCookie(AutoLoginIdentity::ID, AutoLoginIdentity::KEY_CORRECT);
        $response = $middleware->process($request, $this->getRequestHandlerThatReturnsResponse());
        $this->assertMatchesRegularExpression('#autoLogin=%5B%2242%22%2C%22auto-login-key-correct%22%5D; Expires=.*?; Max-Age=604800; Path=/; Secure; HttpOnly; SameSite=Lax#', $response->getHeaderLine('Set-Cookie'));
    }

    public function testRemoveCookieAfterLogout()
    {
        $user = $this->getUserForLogout();
        $autoLogin = $this->getAutoLogin();
        $middleware = new AutoLoginMiddleware(
            $user,
            $this->getAutoLoginIdentityRepository(),
            $this->logger,
            $autoLogin
        );
        $request = $this->getRequestWithAutoLoginCookie(AutoLoginIdentity::ID, AutoLoginIdentity::KEY_CORRECT);
        $response = $middleware->process($request, $this->getRequestHandlerThatReturnsResponse());
        $this->assertMatchesRegularExpression('#autoLogin=; Expires=.*?; Max-Age=-31622400; Path=/; Secure; HttpOnly; SameSite=Lax#', $response->getHeaderLine('Set-Cookie'));
    }

    private function getRequestHandler(): RequestHandlerInterface
    {
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $requestHandler
            ->expects($this->once())
            ->method('handle');

        return $requestHandler;
    }

    private function getRequestHandlerThatIsNotCalled(): RequestHandlerInterface
    {
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $requestHandler
            ->expects($this->never())
            ->method('handle');

        return $requestHandler;
    }

    private function getRequestHandlerThatReturnsResponse(): RequestHandlerInterface
    {
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $response = new Response();

        $requestHandler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        return $requestHandler;
    }

    private function getIncorrectIdentityRepository(): IdentityRepositoryInterface
    {
        return $this->getIdentityRepository($this->createMock(IdentityInterface::class));
    }

    private function getAutoLoginIdentityRepository(): IdentityRepositoryInterface
    {
        return $this->getIdentityRepository(new AutoLoginIdentity());
    }

    private function getEmptyIdentityRepository(): IdentityRepositoryInterface
    {
        return $this->createMock(IdentityRepositoryInterface::class);
    }

    private function getIdentityRepository(IdentityInterface $identity): IdentityRepositoryInterface
    {
        $identityRepository = $this->createMock(IdentityRepositoryInterface::class);

        $identityRepository
            ->expects($this->any())
            ->method('findIdentity')
            ->willReturn($identity);

        return $identityRepository;
    }

    private function getRequestWithAutoLoginCookie(string $userId, string $authKey): ServerRequestInterface
    {
        return $this->getRequestWithCookies(['autoLogin' => json_encode([$userId, $authKey])]);
    }

    private function getRequestWithCookies(array $cookies): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects($this->any())
            ->method('getCookieParams')
            ->willReturn($cookies);

        return $request;
    }

    private function getUserWithoutLoginExpected(): User
    {
        $user = $this->createMock(User::class);
        $user->expects($this->never())->method('login');
        return $user;
    }

    private function getUserWithLoginExpected(): User
    {
        $user = $this->createMock(User::class);
        $user
            ->expects($this->once())
            ->method('login')
            ->willReturn(true);

        return $user;
    }

    private function getUserForSuccessfulAutologin(): User
    {
        $user = $this->createMock(User::class);
        $user
            ->expects($this->once())
            ->method('login')
            ->willReturn(true);

        $isUserGuest = true;

        $user
            ->method('isGuest')
            ->willReturnCallback(function () use (&$isUserGuest) {
                $isUserGuest = !$isUserGuest;

                return !$isUserGuest;
            })
        ;

        $user
            ->method('getIdentity')
            ->with(false)
            ->willReturn(new AutoLoginIdentity());

        return $user;
    }

    private function getUserForLogout(): User
    {
        $user = $this->createMock(User::class);
        $isUserGuest = false;

        $user
            ->method('isGuest')
            ->willReturnCallback(function () use (&$isUserGuest) {
                $isUserGuest = !$isUserGuest;

                return !$isUserGuest;
            })
        ;

        return $user;
    }

    private function getAutoLogin(): AutoLogin
    {
        return new AutoLogin(new \DateInterval('P1W'));
    }

    /**
     * Gets an inaccessible object property.
     *
     * @param $object
     * @param $propertyName
     * @param bool $revoke whether to make property inaccessible after getting
     *
     * @throws \ReflectionException
     *
     * @return mixed
     */
    private function getInaccessibleProperty($object, $propertyName, bool $revoke = true)
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
