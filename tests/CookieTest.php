<?php

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\Cookie;

use function Sodium\add;

final class CookieTest extends TestCase
{
    private function getCookieHeader(Cookie $cookie): string
    {
        $response = new Response();
        $response = $cookie->addToResponse($response);
        return $response->getHeaderLine('Set-Cookie');
    }

    public function testInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cookie('test[]', 42);
    }

    public function testInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cookie('test', ';');
    }

    public function testDefaults(): void
    {
        $cookie = new Cookie('test', 42);

        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testDomain(): void
    {
        $cookie = (new Cookie('test', 42))->domain('yiiframework.com');

        $this->assertSame('test=42; Domain=yiiframework.com; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testExpireAt(): void
    {
        $expireDateTime = new \DateTime();
        $expireDateTime->setTimezone(new \DateTimeZone('GMT'));
        $formattedDateTime = $expireDateTime->format(\DateTimeInterface::RFC7231);

        $cookie = (new Cookie('test', 42))->expireAt($expireDateTime);

        $this->assertSame("test=42; Expires=$formattedDateTime; Path=/; Secure; HttpOnly; SameSite=Lax", $this->getCookieHeader($cookie));
    }

    public function testMaxAge(): void
    {
        $maxAge = new \DateInterval('PT3600S');
        $formattedDateTime = (new \DateTimeImmutable())->add($maxAge)->format(\DateTimeInterface::RFC7231);

        $cookie = (new Cookie('test', 42))->maxAge($maxAge);
        $this->assertSame("test=42; Expires=$formattedDateTime; Max-Age=3600; Path=/; Secure; HttpOnly; SameSite=Lax", $this->getCookieHeader($cookie));
    }

    public function testIsExpired(): void
    {
        $cookie = (new Cookie('test', 42))->expireAt((new \DateTimeImmutable('-5 years')));
        $this->assertTrue($cookie->isExpired());
    }

    public function testExpire(): void
    {
        $formattedDateTime = (new \DateTimeImmutable('-5 years'))->format(\DateTimeInterface::RFC7231);
        $cookie = (new Cookie('test', 42))->expire();
        $this->assertSame("test=42; Expires=$formattedDateTime; Path=/; Secure; HttpOnly; SameSite=Lax", $this->getCookieHeader($cookie));
    }

    public function testExpireWhenBrowserIsClosed(): void
    {
        $cookie = (new Cookie('test', 42))->expireWhenBrowserIsClosed();

        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testPath(): void
    {
        $cookie = (new Cookie('test', 42))->path('/test');

        $this->assertSame('test=42; Path=/test; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testInvalidPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Cookie('test', 42))->path(';invalid');
    }

    public function testSecure(): void
    {
        $cookie = (new Cookie('test', 42))->secure(false);

        $this->assertSame('test=42; Path=/; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testHttpOnly(): void
    {
        $cookie = (new Cookie('test', 42))->httpOnly(false);

        $this->assertSame('test=42; Path=/; Secure; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testInvalidSameSite(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Cookie('test', 42))->sameSite('invalid');
    }

    public function testSameSiteNone(): void
    {
        $cookie = (new Cookie('test', 42))->sameSite(Cookie::SAME_SITE_NONE);

        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=None', $this->getCookieHeader($cookie));
    }

    public function testFromSetCookieString(): void
    {
        $expireDate = new \DateTimeImmutable();
        $maxAge = new \DateInterval('PT3600S');
        $setCookieString = 'sessionId=e8bb43229de9; Domain=foo.example.com; ';
        $setCookieString .= 'Expires=' . $expireDate->format(\DateTimeInterface::RFC7231) . '; ';
        $setCookieString .= 'Max-Age=3600; Path=/; Secure; HttpOnly; SameSite=Strict';

        $cookie = (new Cookie('sessionId', 'e8bb43229de9', false))
            ->expireAt($expireDate)
            ->maxAge($maxAge)
            ->domain('foo.example.com')
            ->path('/')
            ->secure(true)
            ->httpOnly(true)
            ->sameSite(Cookie::SAME_SITE_STRICT);
        $cookie2 = Cookie::fromSetCookieString($setCookieString);

        $this->assertSame((string)$cookie, (string)$cookie2);
    }
}
