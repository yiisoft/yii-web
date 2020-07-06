<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\User;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Yii\Web\Session\SessionInterface;
use Yiisoft\Yii\Web\Tests\Flash\MockArraySessionStorage;
use Yiisoft\Yii\Web\Tests\Mock\MockAccessChecker;
use Yiisoft\Yii\Web\Tests\Mock\MockEventDispatcher;
use Yiisoft\Yii\Web\Tests\Mock\MockIdentity;
use Yiisoft\Yii\Web\Tests\Mock\MockIdentityRepository;
use Yiisoft\Yii\Web\User\Event\AfterLogin;
use Yiisoft\Yii\Web\User\Event\AfterLogout;
use Yiisoft\Yii\Web\User\Event\BeforeLogin;
use Yiisoft\Yii\Web\User\Event\BeforeLogout;
use Yiisoft\Yii\Web\User\GuestIdentity;
use Yiisoft\Yii\Web\User\User;

final class UserTest extends TestCase
{
    public function testGetIdentityMethodReturnGuestWithoutSession(): void
    {
        $user = new User(
            $this->createIdentityRepository(),
            $this->createDispatcher()
        );

        $this->assertInstanceOf(GuestIdentity::class, $user->getIdentity());
    }

    public function testGetIdentityMethodReturnGuestWithSession(): void
    {
        $user = new User(
            $this->createIdentityRepository(),
            $this->createDispatcher(),
            $this->createSessionStorage()
        );

        $this->assertInstanceOf(GuestIdentity::class, $user->getIdentity());
    }

    public function testGetIdentityMethodReturnCorrectIdentity(): void
    {
        $user = new User(
            $this->createIdentityRepository(),
            $this->createDispatcher()
        );

        $user->setIdentity($this->createIdentity('test-id'));
        $this->assertEquals('test-id', $user->getIdentity()->getId());
    }

    public function testGetIdentityReturnGuestBecauseSessionIsExpireAuthTimeout(): void
    {
        $repository = $this->createIdentityRepository(
            $this->createIdentity('test-id')
        );

        $sessionStorage = $this->createSessionStorage(
            [
                '__auth_id' => 'test-id',
                '__auth_expire' => strtotime('-1 day')
            ]
        );

        $user = new User(
            $repository,
            $this->createDispatcher(),
            $sessionStorage
        );

        $user->authTimeout = 60;

        $this->assertInstanceOf(GuestIdentity::class, $user->getIdentity());
    }

    public function testGetIdentityReturnGuestBecauseSessionIsExpireAbsoluteAuthTimeout(): void
    {
        $repository = $this->createIdentityRepository(
            $this->createIdentity('test-id')
        );

        $sessionStorage = $this->createSessionStorage(
            [
                '__auth_id' => 'test-id',
                '__auth_absolute_expire' => strtotime('-1 day')
            ]
        );

        $user = new User(
            $repository,
            $this->createDispatcher(),
            $sessionStorage
        );

        $user->absoluteAuthTimeout = 60;

        $this->assertInstanceOf(GuestIdentity::class, $user->getIdentity());
    }

    public function testGetIdentityExpectException(): void
    {
        $this->expectException(\Exception::class);

        $user = new User(
            $this->createIdentityRepositoryWithException(),
            $this->createDispatcher(),
            $this->createSessionStorage(['__auth_id' => '123456'])
        );

        $user->getIdentity();
    }

    public function testMethodGetIdentityReturnCorrectValueAndSetAuthExpire(): void
    {
        $repository = $this->createIdentityRepository(
            $this->createIdentity('test-id')
        );
        $sessionStorage = $this->createSessionStorage(['__auth_id' => 'test-id']);
        $user = new User($repository, $this->createDispatcher(), $sessionStorage);

        $user->authTimeout = 60;

        $this->assertEquals('test-id', $user->getIdentity()->getId());
        $this->assertTrue($sessionStorage->has('__auth_expire'));
    }

    public function testLogin(): void
    {
        $dispatcher = $this->createDispatcher();

        $user = new User(
            $this->createIdentityRepository(),
            $dispatcher,
            $this->createSessionStorage()
        );

        $this->assertTrue($user->login($this->createIdentity('test-id')));
        $this->assertEquals(
            [
                BeforeLogin::class,
                AfterLogin::class
            ],
            $dispatcher->getClassesEvents()
        );

        $this->assertEquals('test-id', $user->getIdentity()->getId());
    }

    public function testGetIdReturnCorrectValue(): void
    {
        $user = new User(
            $this->createIdentityRepository(),
            $this->createDispatcher()
        );

        $user->setIdentity($this->createIdentity('test-id'));
        $this->assertEquals('test-id', $user->getId());
    }

    public function testGetIdReturnNull(): void
    {
        $user = new User(
            $this->createIdentityRepository(),
            $this->createDispatcher()
        );

        $this->assertNull($user->getId());
    }

    public function testLogoutReturnTrue(): void
    {
        $dispatcher = $this->createDispatcher();
        $user = new User(
            $this->createIdentityRepository(),
            $dispatcher
        );

        $user->setIdentity($this->createIdentity('test-id'));

        $this->assertTrue($user->logout());
        $this->assertEquals(
            [
                BeforeLogout::class,
                AfterLogout::class
            ],
            $dispatcher->getClassesEvents()
        );
        $this->assertTrue($user->isGuest());
    }

    public function testLogoutReturnFalseBecauseUserIsGuest(): void
    {
        $dispatcher = $this->createDispatcher();
        $repository = $this->createIdentityRepository(
            $this->createIdentity('test-id')
        );

        $user = new User($repository, $dispatcher);

        $this->assertFalse($user->logout());
        $this->assertEmpty($dispatcher->getClassesEvents());
        $this->assertTrue($user->isGuest());
    }

    public function testLogoutWithSession(): void
    {
        $identity = $this->createIdentity('test-id');

        $sessionStorage = $this->createSessionStorage();
        $sessionStorage->open();

        $user = new User(
            $this->createIdentityRepository($identity),
            $this->createDispatcher(),
            $sessionStorage
        );

        $user->setIdentity($identity);

        $this->assertTrue($user->logout());
        $this->assertFalse($sessionStorage->isActive());
    }

    public function testLoginByAccessToken(): void
    {
        $dispatcher = $this->createDispatcher();
        $repository = $this->createIdentityRepository(
            $this->createIdentity('test-id')
        );

        $user = new User($repository, $dispatcher, $this->createSessionStorage());
        $result = $user->loginByAccessToken('token', 'type');

        $this->assertEquals('test-id', $result->getId());
        $this->assertEquals('test-id', $user->getIdentity()->getId());
        $this->assertEquals(
            [
                BeforeLogin::class,
                AfterLogin::class
            ],
            $dispatcher->getClassesEvents()
        );
    }

    public function testLoginByAccessTokenReturnNullBecauseIdentityNotFound(): void
    {
        $dispatcher = $this->createDispatcher();

        $user = new User(
            $this->createIdentityRepository(),
            $dispatcher,
            $this->createSessionStorage()
        );

        $result = $user->loginByAccessToken('token', 'type');

        $this->assertNull($result);
        $this->assertEmpty(
            $dispatcher->getClassesEvents()
        );
    }

    public function testCanMethodReturnFalseBecauseCheckerNotSet(): void
    {
        $user = new User(
            $this->createIdentityRepository(),
            $this->createDispatcher(),
            $this->createSessionStorage()
        );

        $this->assertFalse($user->can('permission'));
    }

    public function testCanMethodReturnTrue(): void
    {
        $user = new User(
            $this->createIdentityRepository(),
            $this->createDispatcher(),
            $this->createSessionStorage()
        );

        $user->setAccessChecker($this->createAccessChecker());

        $this->assertTrue($user->can('permission'));
    }

    public function testSwitchIdentityReturnCorrectValue(): void
    {
        $expire = strtotime('+1 day');
        $sessionStorage = $this->createSessionStorage(
            [
                '__auth_id' => 'test-id',
                '__auth_expire' => $expire
            ]
        );

        $user = new User(
            $this->createIdentityRepository(),
            $this->createDispatcher(),
            $sessionStorage
        );

        $user->authTimeout = 60;
        $user->absoluteAuthTimeout = 3600;

        $user->setIdentity($this->createIdentity('test-id'));
        $user->switchIdentity($this->createIdentity('test-id-2'));

        $this->assertEquals('test-id-2', $user->getIdentity()->getId());
        $this->assertNotEquals('test-id', $sessionStorage->get('__auth_id'));
        $this->assertNotEquals($expire, $sessionStorage->get('__auth_expire'));
        $this->assertTrue($sessionStorage->has('__auth_expire'));
        $this->assertTrue($sessionStorage->has('__auth_absolute_expire'));
    }

    public function testSwitchIdentityToGuest(): void
    {
        $user = new User(
            $this->createIdentityRepository(),
            $this->createDispatcher()
        );

        $user->authTimeout = 60;

        $user->setIdentity($this->createIdentity('test-id'));
        $user->switchIdentity(new GuestIdentity());

        $this->assertInstanceOf(GuestIdentity::class, $user->getIdentity());
    }

    private function createIdentityRepository(?IdentityInterface $identity = null): IdentityRepositoryInterface
    {
        return new MockIdentityRepository($identity);
    }

    private function createIdentityRepositoryWithException(): IdentityRepositoryInterface
    {
        $repository = new MockIdentityRepository();
        $repository->withException();

        return $repository;
    }

    private function createDispatcher(): EventDispatcherInterface
    {
        return new MockEventDispatcher();
    }

    private function createIdentity(string $id): IdentityInterface
    {
        return new MockIdentity($id);
    }

    private function createSessionStorage(array $data = []): SessionInterface
    {
        return new MockArraySessionStorage($data);
    }

    private function createAccessChecker(): AccessCheckerInterface
    {
        return new MockAccessChecker(true);
    }
}
