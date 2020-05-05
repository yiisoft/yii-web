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
    private string $cookieName = 'remember';

    public function cookieName(string $name): self
    {
        $new = clone $this;
        $new->cookieName = $name;
        return $new;
    }

    /**
     * Sends an identity cookie.
     * TODO: do it on event?
     * TODO: make duration a property of the service?
     *
     * @param AutoLoginIdentityInterface $identity
     * @param int $duration number of seconds that the user can remain in logged-in status.
     * @param ResponseInterface $response Response to handle
     */
    public function sendCookie(AutoLoginIdentityInterface $identity, int $duration, ResponseInterface $response): void
    {
        $data = json_encode(
            [
                $identity->getId(),
                $identity->getAuthKey(),
                // $duration, TODO: should we set/check duration separately from cookie expiration?
            ],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        $expireDateTime = new \DateTimeImmutable();
        $expireDateTime->setTimestamp(time() + $duration);
        $cookieIdentity = (new Cookie($this->cookieName, $data))->expireAt($expireDateTime);
        $cookieIdentity->addToResponse($response);
    }

    /**
     * Remove auto-login cookie so user is not logged in automatically anymore.
     * TODO: trigger on logout?
     */
    public function removeCookie(): void
    {
        // Remove the cookie
        // TODO: use new cookie methods
        (new Cookie($this->cookieName, ""))->expire(1);
    }

    public function getCookieName(): string
    {
        return $this->cookieName;
    }
}
