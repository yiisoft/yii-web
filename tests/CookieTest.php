<?php

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\Cookie;

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

    public function testDefaults(): void
    {
        $cookie = new Cookie('test', 42);

        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testWithValue(): void
    {
        $cookie = (new Cookie('test'))->withValue(42);
        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Cookie('test'))->withValue(';');
    }

    public function testWithExpires(): void
    {
        $expireDateTime = new \DateTime('+1 year');
        $expireDateTime->setTimezone(new \DateTimeZone('GMT'));
        $formattedDateTime = $expireDateTime->format(\DateTimeInterface::RFC7231);
        $maxAge = $expireDateTime->getTimestamp() - time();

        $cookie = (new Cookie('test', 42))->withExpires($expireDateTime);

        $this->assertSame("test=42; Expires=$formattedDateTime; Max-Age=$maxAge; Path=/; Secure; HttpOnly; SameSite=Lax", $this->getCookieHeader($cookie));
    }

    public function testIsExpired(): void
    {
        $cookie = (new Cookie('test', 42))->withExpires((new \DateTimeImmutable('-5 years')));
        $this->assertTrue($cookie->isExpired());
    }

    public function testWithMaxAge(): void
    {
        $formattedExpire = (new \DateTimeImmutable())->setTimestamp(time() + 3600)->format(\DateTimeInterface::RFC7231);
        $cookie = (new Cookie('test', 42))->withMaxAge(new \DateInterval('PT3600S'));

        $this->assertSame("test=42; Expires=$formattedExpire; Max-Age=3600; Path=/; Secure; HttpOnly; SameSite=Lax", $this->getCookieHeader($cookie));
    }

    public function testExpire(): void
    {
        $cookie = (new Cookie('test', 42))->expire();
        $this->assertTrue($cookie->isExpired());
    }

    public function testNegativeInterval(): void
    {
        $formattedExpire = (new \DateTimeImmutable())->setTimestamp(time() - 3600)->format(\DateTimeInterface::RFC7231);
        $negativeInterval = new \DateInterval('PT3600S');
        $negativeInterval->invert = 1;
        $cookie = (new Cookie('test', 42))->withMaxAge($negativeInterval);

        $this->assertSame("test=42; Expires=$formattedExpire; Max-Age=-3600; Path=/; Secure; HttpOnly; SameSite=Lax", $this->getCookieHeader($cookie));
    }

    public function testWithDomain(): void
    {
        $cookie = (new Cookie('test', 42))->withDomain('yiiframework.com');

        $this->assertSame('test=42; Domain=yiiframework.com; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testExpireWhenBrowserIsClosed(): void
    {
        $cookie = (new Cookie('test', 42))->expireWhenBrowserIsClosed();

        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testWithPath(): void
    {
        $cookie = (new Cookie('test', 42))->withPath('/test');

        $this->assertSame('test=42; Path=/test; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testInvalidPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Cookie('test', 42))->withPath(';invalid');
    }

    public function testWithSecure(): void
    {
        $cookie = (new Cookie('test', 42))->withSecure(false);

        $this->assertSame('test=42; Path=/; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testHttpOnly(): void
    {
        $cookie = (new Cookie('test', 42))->withHttpOnly(false);

        $this->assertSame('test=42; Path=/; Secure; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testInvalidSameSite(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Cookie('test', 42))->withSameSite('invalid');
    }

    public function testSameSiteNone(): void
    {
        $cookie = (new Cookie('test', 42))->withSameSite(Cookie::SAME_SITE_NONE);

        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=None', $this->getCookieHeader($cookie));
    }

    public function testFromCookieString(): void
    {
        $expireDate = new \DateTimeImmutable('+60 minutes');
        $setCookieString = 'sessionId=e8bb43229de9; Domain=foo.example.com; ';
        $setCookieString .= 'Expires=' . $expireDate->format(\DateTimeInterface::RFC7231) . '; ';
        $setCookieString .= 'Max-Age=3600; Path=/; Secure; SameSite=Strict; ExtraKey';

        $cookie = new Cookie(
            'sessionId',
            'e8bb43229de9',
            $expireDate,
            'foo.example.com',
            '/',
            true,
            false,
            Cookie::SAME_SITE_STRICT
        );
        $cookie2 = Cookie::fromCookieString($setCookieString);

        $this->assertSame((string)$cookie, (string)$cookie2);
    }

    public function testFromCookieStringWithInvalidString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Cookie::fromCookieString('');
    }

    public function testGetters(): void
    {
        $cookie = new Cookie('test', '', null, null, null, null, null, null);
        $this->assertEquals('test', $cookie->getName());
        $this->assertEquals('', $cookie->getValue());
        $this->assertNull($cookie->getExpires());
        $this->assertNull($cookie->getDomain());
        $this->assertNull($cookie->getPath());
        $this->assertFalse($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertNull($cookie->getSameSite());

        $cookie = $cookie->withValue('testValue');
        $this->assertEquals('testValue', $cookie->getValue());

        $expiry = new \DateTimeImmutable();
        $cookie = $cookie->withExpires($expiry);
        $this->assertEquals($expiry->getTimestamp(), $cookie->getExpires()->getTimestamp());

        $cookie = $cookie->withDomain('yiiframework.com');
        $this->assertEquals('yiiframework.com', $cookie->getDomain());

        $cookie = $cookie->withPath('/path');
        $this->assertEquals('/path', $cookie->getPath());

        $cookie = $cookie->withSecure(true);
        $this->assertTrue($cookie->isSecure());

        $cookie = $cookie->withHttpOnly(true);
        $this->assertTrue($cookie->isHttpOnly());

        $cookie = $cookie->withSameSite(Cookie::SAME_SITE_LAX);
        $this->assertEquals(Cookie::SAME_SITE_LAX, $cookie->getSameSite());
    }
}
