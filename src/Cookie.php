<?php
declare(strict_types=1);

namespace Yiisoft\Yii\Web;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

use function array_filter;
use function array_map;
use function array_shift;
use function explode;
use function implode;
use function in_array;
use function preg_match;
use function preg_split;
use function strtolower;

/**
 * Cookie helps adding Set-Cookie header response in order to set cookie
 */
final class Cookie
{
    // @see https://tools.ietf.org/html/rfc6265#section-4
    // @see https://tools.ietf.org/html/rfc2616#section-2.2
    private const TOKEN = '/^[a-zA-Z0-9!#$%&\' * +\- .^_`|~]+$/';
    private const OCTET='/^[\x21\x23-\x2B\x2D-\x3A\x3C-\x5B\x5D-\x7E]*$/';

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

    public const SAME_SITE_NONE = 'None';

    /**
     * @var string name of the cookie
     */
    private string $name;

    /**
     * @var string value of the cookie
     */
    private string $value = '';

    /**
     * @var string|null RFC-1123 date at which the cookie expires.
     * @see https://tools.ietf.org/html/rfc6265#section-4.1.1
     */
    private ?string $expire = null;

    /**
     * @var string|null domain of the cookie.
     */
    private ?string $domain = null;

    /**
     * @var string|null the path on the server in which the cookie will be available on.
     */
    private ?string $path = null;

    /**
     * @var bool|null whether cookie should be sent via secure connection
     */
    private ?bool $secure = null;

    /**
     * @var bool|null whether the cookie should be accessible only through the HTTP protocol.
     * By setting this property to true, the cookie will not be accessible by scripting languages,
     * such as JavaScript, which can effectively help to reduce identity theft through XSS attacks.
     */
    private ?bool $httpOnly = null;

    /**
     * @var string|null SameSite prevents the browser from sending this cookie along with cross-site requests.
     * @see https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#samesite-cookie-attribute for more information about sameSite.
     */
    private ?string $sameSite = null;

    public function __construct(string $name, string $value = '', bool $safeDefaults = true)
    {
        if (!preg_match(self::TOKEN, $name)) {
            throw new InvalidArgumentException("The cookie name \"$name\" contains invalid characters.");
        }

        $this->name = $name;
        $this->setValue($value);

        if ($safeDefaults) {
            $this->path = '/';
            $this->secure = true;
            $this->httpOnly = true;
            $this->sameSite = self::SAME_SITE_LAX;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function expireAt(DateTimeInterface $dateTime): self
    {
        $new = clone $this;
        $new->expire = $dateTime->format('D, d M Y H:i:s T');
        return $new;
    }

    public function validFor(DateInterval $dateInterval): self
    {
        $expireDateTime = (new DateTimeImmutable())->add($dateInterval);
        return $this->expireAt($expireDateTime);
    }

    public function expireWhenBrowserIsClosed(): self
    {
        $new = clone $this;
        $new->expire = null;
        return $new;
    }

    public function domain(string $domain): self
    {
        $new = clone $this;
        $new->domain = $domain;
        return $new;
    }

    public function path(string $path): self
    {
        // path value is defined as any character except CTLs or ";"
        if (preg_match('/[\x00-\x1F\x7F\x3B]/', $path)) {
            throw new InvalidArgumentException("The cookie path \"$path\" contains invalid characters.");
        }

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
            throw new InvalidArgumentException('sameSite should be one of "Lax", "Strict" or "None"');
        }

        if ($sameSite === self::SAME_SITE_NONE) {
            // the secure flag is required for cookies that are marked as 'SameSite=None'
            $this->secure = true;
        }

        $new = clone $this;
        $new->sameSite = $sameSite;
        return $new;
    }

    private function setValue(string $value): void
    {
        // @see https://tools.ietf.org/html/rfc6265#section-4.1.1
        if (!preg_match(self::OCTET, $value)) {
            throw new InvalidArgumentException("The cookie value \"$value\" contains invalid characters.");
        }

        $this->value = $value;
    }

    public function addToResponse(ResponseInterface $response): ResponseInterface
    {
        return $response->withAddedHeader('Set-Cookie', (string) $this);
    }

    public function __toString(): string
    {
        $cookieParts = [
            $this->name . '=' . $this->value
        ];

        if ($this->expire) {
            $cookieParts[] = 'Expires=' . $this->expire;
        }

        if ($this->domain) {
            $cookieParts[] = 'Domain=' . $this->domain;
        }

        if ($this->path) {
            $cookieParts[] = 'Path=' . $this->path;
        }

        if ($this->secure) {
            $cookieParts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $cookieParts[] = 'HttpOnly';
        }

        if ($this->sameSite) {
            $cookieParts[] = 'SameSite=' . $this->sameSite;
        }

        return implode('; ', $cookieParts);
    }

    /**
     * Parse 'Set-Cookie' string and build Cookie object.
     * Pass only Set-Cookie header value.
     *
     * @param string $string 'Set-Cookie' header value
     * @return self
     * @throws Exception
     */
    public static function fromSetCookieString(string $string): self
    {
        // array_filter with empty callback is used to filter out all falsy values
        $rawAttributes = array_filter(preg_split('~\s*[;]\s*~', $string));

        $rawAttribute = array_shift($rawAttributes);

        if (!is_string($rawAttribute)) {
            throw new InvalidArgumentException('Cookie string must have at least on attribute');
        }

        [$cookieName, $cookieValue] = self::splitCookieAttribute($rawAttribute);

        $cookie = new self($cookieName, $cookieValue ?? '', false);

        while ($rawAttribute = array_shift($rawAttributes)) {
            [$attributeKey, $attributeValue] = self::splitCookieAttribute($rawAttribute);
            $attributeKey = strtolower($attributeKey);

            if ($attributeValue === null && ($attributeKey !== 'secure' || $attributeKey !== 'httponly')) {
                continue;
            }

            switch (strtolower($attributeKey)) {
                case 'expires':
                    $cookie = $cookie->expireAt(new DateTimeImmutable($attributeValue));
                    break;
                case 'max-age':
                    $cookie = $cookie->validFor(new DateInterval('PT' . $attributeValue . 'S'));
                    break;
                case 'domain':
                    $cookie = $cookie->domain($attributeValue);
                    break;
                case 'path':
                    $cookie = $cookie->path($attributeValue);
                    break;
                case 'secure':
                    $cookie = $cookie->secure(true);
                    break;
                case 'httponly':
                    $cookie = $cookie->httpOnly(true);
                    break;
                case 'samesite':
                    $cookie = $cookie->sameSite($attributeValue);
                    break;
            }
        }

        return $cookie;
    }

    private static function splitCookieAttribute(string $attribute): array
    {
        $parts = explode('=', $attribute, 2);
        $parts[1] = $parts[1] ?? null;

        return array_map('urldecode', $parts);
    }
}
