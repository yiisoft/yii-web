<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Yii\Web\Event\AfterEmit;
use Yiisoft\Yii\Web\Event\AfterMiddleware;
use Yiisoft\Yii\Web\Event\AfterRequest;
use Yiisoft\Yii\Web\Event\BeforeMiddleware;
use Yiisoft\Yii\Web\Event\BeforeRequest;
use Yiisoft\Yii\Web\Tests\Mock\MockIdentity;
use Yiisoft\Yii\Web\Tests\Mock\MockMiddleware;
use Yiisoft\Yii\Web\User\Event\AfterLogin;
use Yiisoft\Yii\Web\User\Event\AfterLogout;
use Yiisoft\Yii\Web\User\Event\BeforeLogin;
use Yiisoft\Yii\Web\User\Event\BeforeLogout;

final class EventTest extends TestCase
{
    public function testUserAfterLoginEvent(): void
    {
        $event = new AfterLogin($this->createIdentity('test'));
        $this->assertEquals('test', $event->getIdentity()->getId());
    }

    public function testUserAfterLogoutEvent(): void
    {
        $event = new AfterLogout($this->createIdentity('test'));
        $this->assertEquals('test', $event->getIdentity()->getId());
    }

    public function testUserBeforeLogoutEvent(): void
    {
        $event = new BeforeLogout($this->createIdentity('test'));
        $this->assertEquals('test', $event->getIdentity()->getId());
        $this->assertTrue($event->isValid());
    }

    public function testUserBeforeLogoutEventInvalid(): void
    {
        $event = new BeforeLogout($this->createIdentity('test'));
        $event->invalidate();
        $this->assertFalse($event->isValid());
    }

    public function testUserBeforeLoginEvent(): void
    {
        $event = new BeforeLogin($this->createIdentity('test'));
        $this->assertEquals('test', $event->getIdentity()->getId());
        $this->assertTrue($event->isValid());
    }

    public function testUserBeforeLoginEventInvalid(): void
    {
        $event = new BeforeLogin($this->createIdentity('test'));
        $event->invalidate();
        $this->assertFalse($event->isValid());
    }

    public function testAfterEmitEvent(): void
    {
        $event = new AfterEmit($this->createResponse(400));
        $this->assertEquals(400, $event->getResponse()->getStatusCode());
    }

    public function testAfterEmitEventWithoutResponse(): void
    {
        $event = new AfterEmit(null);
        $this->assertNull($event->getResponse());
    }

    public function testAfterMiddlewareEvent(): void
    {
        $middleware = $this->createMiddleware();
        $event = new AfterMiddleware($middleware, $this->createResponse());
        $this->assertEquals(200, $event->getResponse()->getStatusCode());
        $this->assertEquals($middleware, $event->getMiddleware());
    }

    public function testAfterMiddlewareWithoutResponseEvent(): void
    {
        $event = new AfterMiddleware($this->createMiddleware(), null);
        $this->assertNull($event->getResponse());
    }

    public function testAfterRequestEvent(): void
    {
        $event = new AfterRequest($this->createResponse(400));
        $this->assertEquals(400, $event->getResponse()->getStatusCode());
    }

    public function testBeforeRequestEvent(): void
    {
        $event = new BeforeRequest($this->createRequest());
        $this->assertEquals('PUT', $event->getRequest()->getMethod());
        $this->assertEquals('/test', $event->getRequest()->getUri());
    }

    public function testBeforeMiddlewareEvent(): void
    {
        $middleware = $this->createMiddleware();
        $event = new BeforeMiddleware($middleware, $this->createRequest());
        $this->assertEquals('/test', $event->getRequest()->getUri());
        $this->assertEquals($middleware, $event->getMiddleware());
    }

    private function createIdentity(string $id): IdentityInterface
    {
        return new MockIdentity($id);
    }

    private function createResponse(int $code = 200): ResponseInterface
    {
        return new Response($code);
    }

    private function createMiddleware(int $code = 200): MiddlewareInterface
    {
        return new MockMiddleware($code);
    }

    private function createRequest(): ServerRequestInterface
    {
        return new ServerRequest('PUT', '/test');
    }
}
