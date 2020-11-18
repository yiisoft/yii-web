<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\User;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\Web\Tests\Mock\MockEventDispatcher;
use Yiisoft\Yii\Web\Tests\Mock\MockIdentity;
use Yiisoft\Yii\Web\Tests\Mock\MockIdentityRepository;
use Yiisoft\Yii\Web\Tests\MockArraySessionStorage;
use Yiisoft\Yii\Web\User\User;
use Yiisoft\Yii\Web\User\UserAuth;

final class UserAuthTest extends TestCase
{
    public function testSuccessfulAuthentication(): void
    {
        $user = $this->createUser();
        $user->setIdentity($this->createIdentity('test-id'));
        $result = (new UserAuth($user, new Psr17Factory()))->authenticate($this->createRequest());

        $this->assertNotNull($result);
        $this->assertEquals('test-id', $result->getId());
    }

    public function testIdentityNotAuthenticated(): void
    {
        $user = $this->createUser();
        $result = (new UserAuth($user, new Psr17Factory()))->authenticate($this->createRequest());

        $this->assertNull($result);
    }

    public function testChallengeIsCorrect(): void
    {
        $response = new Response();
        $user = $this->createUser();
        $challenge = (new UserAuth($user, new Psr17Factory()))->challenge($response);

        $this->assertEquals(Status::FOUND, $challenge->getStatusCode());
        $this->assertEquals('/login', $challenge->getHeaderLine('Location'));
    }

    public function testCustomAuthUrl(): void
    {
        $response = new Response();
        $user = $this->createUser();
        $challenge = (new UserAuth($user, new Psr17Factory()))->withAuthUrl('/custom-auth-url')->challenge($response);

        $this->assertEquals('/custom-auth-url', $challenge->getHeaderLine('Location'));
    }

    public function testImmutability(): void
    {
        $original = new UserAuth($this->createUser(), new Psr17Factory());

        $this->assertNotSame($original, $original->withAuthUrl('/custom-auth-url'));
    }

    private function createUser(?IdentityInterface $identity = null): User
    {
        return new User(
            $this->createIdentityRepository($identity),
            $this->createDispatcher(),
            $this->createSessionStorage()
        );
    }

    private function createIdentity(string $id = 'test-id'): IdentityInterface
    {
        return new MockIdentity($id);
    }

    private function createIdentityRepository(?IdentityInterface $identity = null): IdentityRepositoryInterface
    {
        return new MockIdentityRepository($identity);
    }

    private function createDispatcher(): EventDispatcherInterface
    {
        return new MockEventDispatcher();
    }

    private function createSessionStorage(array $data = []): SessionInterface
    {
        return new MockArraySessionStorage($data);
    }

    private function createRequest(array $serverParams = [], array $headers = []): ServerRequestInterface
    {
        return new ServerRequest(Method::GET, '/', $headers, null, '1.1', $serverParams);
    }
}
