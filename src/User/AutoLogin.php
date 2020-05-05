<?php


namespace Yiisoft\Yii\Web\User;


use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\Web\Cookie;

/**
 * The service is used to send or remove auto-login cookie.
 *
 * @see AutoLoginIdentityInterface
 * @see AutoLoginMiddleware
 */
class AutoLogin
{
    private string $cookieName = 'autoLogin';

    public function cookieName(string $name): self
    {
        $new = clone $this;
        $new->cookieName = $name;
        return $new;
    }

    /**
     * Add auto-login cookie to response so the user is logged in automatically based on cookie even if session
     * is expired.
     *
     * TODO: do it on event?
     * TODO: make duration a property of the service?
     *
     * @param AutoLoginIdentityInterface $identity
     * @param int $duration number of seconds that the user can remain in logged-in status.
     * @param ResponseInterface $response Response to handle
     */
    public function addCookie(AutoLoginIdentityInterface $identity, int $duration, ResponseInterface $response): void
    {
        $data = json_encode([
            $identity->getId(),
            $identity->getAuthKey()
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $expireDateTime = new \DateTimeImmutable();
        $expireDateTime->setTimestamp(time() + $duration);
        $cookieIdentity = (new Cookie($this->cookieName, $data))->withExpires($expireDateTime);
        $cookieIdentity->addToResponse($response);
    }

    /**
     * Expire auto-login cookie so user is not logged in automatically anymore.
     * TODO: trigger on logout?
     */
    public function expireCookie(ResponseInterface $response): void
    {
        (new Cookie($this->cookieName))->expire()->addToResponse($response);
    }

    public function getCookieName(): string
    {
        return $this->cookieName;
    }
}
