<?php


namespace Yiisoft\Yii\Web\Tests\User;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\User\AutoLogin;

final class AutoLoginTest extends TestCase
{
    public function testAddCookie(): void
    {
        $autoLogin = new AutoLogin(new \DateInterval('P1W'));

        $identity = new AutoLoginIdentity();

        $response = new Response();
        $response = $autoLogin->addCookie($identity, $response);

        $this->assertMatchesRegularExpression('#autoLogin=%5B%2242%22%2C%22auto-login-key-correct%22%5D; Expires=.*?; Max-Age=604800; Path=/; Secure; HttpOnly; SameSite=Lax#', $response->getHeaderLine('Set-Cookie'));
    }

    public function testRemoveCookie(): void
    {
        $autoLogin = new AutoLogin(new \DateInterval('P1W'));

        $response = new Response();
        $response = $autoLogin->expireCookie($response);

        $this->assertMatchesRegularExpression('#autoLogin=; Expires=.*?; Max-Age=-31622400; Path=/; Secure; HttpOnly; SameSite=Lax#', $response->getHeaderLine('Set-Cookie'));
    }

    public function testAddCookieWithCustomName(): void
    {
        $cookieName = 'testName';
        $autoLogin = (new AutoLogin(new \DateInterval('P1W')))
            ->withCookieName($cookieName);

        $identity = new AutoLoginIdentity();

        $response = new Response();
        $response = $autoLogin->addCookie($identity, $response);

        $this->assertMatchesRegularExpression('#' . $cookieName . '=%5B%2242%22%2C%22auto-login-key-correct%22%5D; Expires=.*?; Max-Age=604800; Path=/; Secure; HttpOnly; SameSite=Lax#', $response->getHeaderLine('Set-Cookie'));
    }

    public function testRemoveCookieWithCustomName(): void
    {
        $cookieName = 'testName';
        $autoLogin = (new AutoLogin(new \DateInterval('P1W')))
            ->withCookieName($cookieName);

        $response = new Response();
        $response = $autoLogin->expireCookie($response);

        $this->assertMatchesRegularExpression('#' . $cookieName . '=; Expires=.*?; Max-Age=-31622400; Path=/; Secure; HttpOnly; SameSite=Lax#', $response->getHeaderLine('Set-Cookie'));
    }
}
