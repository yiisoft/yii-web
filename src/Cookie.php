<?php

namespace Yiisoft\Yii\Web;

use Psr\Http\Message\ResponseInterface;

/**
 * Cookie helps adding Set-Cookie header response in order to set cookie
 */
final class Cookie
{
    /**
     * SameSite policy Lax will prevent the cookie from being sent by the browser in all cross-site browsing context
     * during CSRF-prone request methods (e.g. POST, PUT, PATCH etc).
     * E.g. a POST request from https://otherdomain.com to https://yourdomain.com will not include the cookie, however a GET request will.
     * When a user follows a link from https://otherdomain.com to https://yourdomain.com it will include the cookie
     * @see $sameSite
     */
    public const SAME_SITE_LAX = 'Lax';

    /**
     * SameSite policy Strict will prevent the cookie from being sent by the browser in all cross-site browsing context
     * regardless of the request method and even when following a regular link.
     * E.g. a GET request from https://otherdomain.com to https://yourdomain.com or a user following a link from
     * https://otherdomain.com to https://yourdomain.com will not include the cookie.
     * @see $sameSite
     */
    public const SAME_SITE_STRICT = 'Strict';

    public const SAME_SITE_NONE = '';

    /**
     * @var string name of the cookie
     */
    private string $name;

    /**
     * @var string value of the cookie
     */
    private string $value;

    /**
     * @var string|null domain of the cookie
     */
    private ?string $domain = null;

    /**
     * @var int the timestamp at which the cookie expires. This is the server timestamp.
     * Defaults to 0, meaning "until the browser is closed".
     */
    private int $expire = 0;

    /**
     * @var string the path on the server in which the cookie will be available on. The default is '/'.
     */
    private string $path = '/';

    /**
     * @var bool whether cookie should be sent via secure connection
     */
    private bool $secure = true;

    /**
     * @var bool whether the cookie should be accessible only through the HTTP protocol.
     * By setting this property to true, the cookie will not be accessible by scripting languages,
     * such as JavaScript, which can effectively help to reduce identity theft through XSS attacks.
     */
    private bool $httpOnly = true;

    /**
     * @var string SameSite prevents the browser from sending this cookie along with cross-site requests.
     * Please note that this feature is only supported since PHP 7.3.0
     * For better security, an exception will be thrown if `sameSite` is set while using an unsupported version of PHP.
     * To use this feature across different PHP versions check the version first. E.g.
     * ```php
     * $cookie->sameSite = PHP_VERSION_ID >= 70300 ? yii\web\Cookie::SAME_SITE_LAX : null,
     * ```
     * @see https://www.owasp.org/index.php/SameSite for more information about sameSite.
     */
    private string $sameSite = self::SAME_SITE_LAX;

    public function __construct(string $name, string $value)
    {
        // @see https://tools.ietf.org/html/rfc6265#section-4
        // @see https://tools.ietf.org/html/rfc2616#section-2.2
        if (!preg_match('~^[a-z0-9._\-]+$~i', $name)) {
            throw new \InvalidArgumentException("The cookie name \"$name\" contains invalid characters.");
        }

        $this->name = $name;
        $this->value = $value;
    }

    public function domain(string $domain): self
    {
        $new = clone $this;
        $new->domain = $domain;
        return $new;
    }

    public function validFor(\DateInterval $dateInterval): self
    {
        $expireDateTime = (new \DateTimeImmutable())->add($dateInterval);
        $new = clone $this;
        $new->expire = (int)$expireDateTime->format('U');
        return $new;
    }

    public function expireAt(\DateTimeInterface $dateTime): self
    {
        $new = clone $this;
        $new->expire = (int)$dateTime->format('U');
        return $new;
    }

    public function expireWhenBrowserIsClosed(): self
    {
        $new = clone $this;
        $new->expire = 0;
        return $new;
    }

    public function path(string $path): self
    {
        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    public function secure(bool $secure): self
    {
        $new = clone $this;
        $new->secure = $secure;
        return $new;
    }

    public function httpOnly(bool $httpOnly): self
    {
        $new = clone $this;
        $new->httpOnly = $httpOnly;
        return $new;
    }

    public function sameSite(string $sameSite): self
    {
        if (!in_array($sameSite, [self::SAME_SITE_LAX, self::SAME_SITE_STRICT, self::SAME_SITE_NONE], true)) {
            throw new \InvalidArgumentException('sameSite should be either Lax or Strict');
        }

        $new = clone $this;
        $new->sameSite = $sameSite;
        return $new;
    }

    public function addToResponse(ResponseInterface $response): ResponseInterface
    {
        $headerValue = $this->name . '=' . urlencode($this->value);

        if ($this->expire !== 0) {
            $headerValue .= '; Expires=' . gmdate('D, d-M-Y H:i:s T', $this->expire);
        }
        if (empty($this->path) === false) {
            $headerValue .= '; Path=' . $this->path;
        }
        if (empty($this->domain) === false) {
            $headerValue .= '; Domain=' . $this->domain;
        }
        if ($this->secure) {
            $headerValue .= '; Secure';
        }
        if ($this->httpOnly) {
            $headerValue .= '; HttpOnly';
        }
        if ($this->sameSite !== '') {
            $headerValue .= '; SameSite=' . $this->sameSite;
        }

        return $response->withAddedHeader('Set-Cookie', $headerValue);
    }
}
