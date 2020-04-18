<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Countable;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Http\Message\ResponseInterface;

use function array_keys;
use function array_values;
use function count;
use function in_array;

/**
 * A CookieCollection is a collection implementation to wrap an array of Cookie class instances.
 * It also helps to work with many cookies at once and, to read and modify cookies from response.
 *
 * @see Cookie
 */
final class CookieCollection implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * @var Cookie[] the cookies in this collection (indexed by the cookie name)
     */
    private array $cookies = [];

    /**
     * CookieCollection constructor.
     *
     * @param array $cookies the cookies that this collection initially contains.
     */
    public function __construct(array $cookies = [])
    {
        foreach ($cookies as $cookie) {
            if (!($cookie instanceof Cookie)) {
                throw new InvalidArgumentException('CookieCollection can contain only Cookie instances.');
            }

            $this->cookies[$cookie->getName()] = $cookie;
        }
    }

    /**
     * Returns the collection as a PHP array.
     * The array keys are cookie names, and the array values are the corresponding cookie objects.
     *
     * @return Cookie[]
     */
    public function toArray(): array
    {
        return $this->cookies;
    }

    /**
     * Returns an iterator for traversing the cookies in the collection.
     * This method is required by the SPL interface [[\IteratorAggregate]].
     * It will be implicitly called when you use `foreach` to traverse the collection.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->cookies);
    }

    /**
     * Returns whether there is a cookie with the specified name.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `isset($collection[$name])`.
     * This is equivalent to [[has()]].
     *
     * @param string $name the cookie name
     * @return bool whether the named cookie exists
     */
    public function offsetExists($name): bool
    {
        return $this->has($name);
    }

    /**
     * Returns the cookie with the specified name.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `$cookie = $collection[$name];`.
     * This is equivalent to [[get()]].
     *
     * @param string $name the cookie name
     * @return Cookie the cookie with the specified name, null if the named cookie does not exist.
     */
    public function offsetGet($name): Cookie
    {
        return $this->get($name);
    }

    /**
     * Adds the cookie to the collection.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `$collection[$name] = $cookie;`.
     * This is equivalent to [[add()]].
     *
     * @param string $name the cookie name
     * @param Cookie $cookie the cookie to be added
     */
    public function offsetSet($name, $cookie): void
    {
        $this->add($cookie);
    }

    /**
     * Removes the named cookie.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `unset($collection[$name])`.
     * This is equivalent to [[remove()]].
     *
     * @param string $name the cookie name
     */
    public function offsetUnset($name): void
    {
        $this->remove($name);
    }

    /**
     * Returns the number of cookies in the collection.
     * This method is required by the SPL `Countable` interface.
     * It will be implicitly called when you use `count($collection)`.
     *
     * @return int the number of cookies in the collection.
     */
    public function count(): int
    {
        return count($this->cookies);
    }

    /**
     * Returns the cookie with the specified name.
     *
     * @param string $name the cookie name
     * @return Cookie the cookie with the specified name. Null if the named cookie does not exist.
     * @see getValue()
     */
    public function get(string $name): Cookie
    {
        return $this->cookies[$name];
    }

    /**
     * Returns the value of the named cookie.
     *
     * @param string $name the cookie name
     * @param mixed $defaultValue the value that should be returned when the named cookie does not exist.
     * @return string|null the value of the named cookie or the default value if cookie is not set.
     * @see get()
     */
    public function getValue(string $name, $defaultValue = null): ?string
    {
        return isset($this->cookies[$name]) ? $this->cookies[$name]->getValue() : $defaultValue;
    }

    /**
     * Adds a cookie to the collection.
     * If there is already a cookie with the same name in the collection, it will be removed first.
     *
     * @param Cookie $cookie the cookie to be added
     */
    public function add(Cookie $cookie): void
    {
        $this->cookies[$cookie->getName()] = $cookie;
    }

    /**
     * Returns whether there is a cookie with the specified name.
     *
     * @param string $name the cookie name
     * @return bool whether the named cookie exists
     * @see remove()
     */
    public function has(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    /**
     * Removes a cookie.
     *
     * @param string $name the name of the cookie to be removed.
     * @return Cookie|null cookie that is removed
     */
    public function remove(string $name): ?Cookie
    {
        if (!isset($this->cookies[$name])) {
            return null;
        }

        $removed = $this->cookies[$name];
        unset($this->cookies[$name]);

        return $removed;
    }

    /**
     * Set cookie expire date to outdated to further remove it from browser.
     *
     * @param string $name the name of the cookie to expire.
     */
    public function expire(string $name): void
    {
        if (!isset($this->cookies[$name])) {
            return;
        }

        $this->cookies[$name] = $this->cookies[$name]->withExpires(new DateTimeImmutable('-1 year'));
    }

    /**
     * Removes all cookies.
     */
    public function clear(): void
    {
        $this->cookies = [];
    }

    /**
     * Returns whether the collection already contains the cookie.
     *
     * @param Cookie $cookie the cookie to check for
     * @return bool whether cookie exists
     * @see has()
     */
    public function contains(Cookie $cookie): bool
    {
        return in_array($cookie, $this->cookies, true);
    }

    /**
     * Tests for the existence of the cookie that satisfies the given predicate.
     *
     * @param Closure $p The predicate.
     * @return bool whether the predicate is true for at least on cookie.
     */
    public function exists(Closure $p): bool
    {
        foreach ($this->cookies as $name => $cookie) {
            if ($p($name, $cookie)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets all names/indices of the collection.
     *
     * @return array The names/indices of the collection.
     */
    public function getNames(): array
    {
        return array_keys($this->cookies);
    }

    /**
     * Gets all cookies of the collection as an indexed array.
     *
     * @return array The cookie in the collection, in the order they appear in the collection.
     */
    public function getCookies(): array
    {
        return array_values($this->cookies);
    }

    /**
     * Checks whether the collection is empty (contains no cookies).
     *
     * @return bool whether the collection is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->cookies);
    }

    /**
     * Populates the cookie collection from an array of 'name' => 'value' pairs.
     *
     * @param array $array the cookies to populate from
     * @return static collection created from array
     */
    public static function fromArray(array $array): self
    {
        // check if associative array with 'name' => 'value' pairs is passed
        if (count(array_filter(array_keys($array), 'is_string')) === 0) {
            throw new InvalidArgumentException('Array in wrong format is passed.');
        }

        $elements = [];
        foreach ($array as $name => $value) {
            $elements[$name] = new Cookie($name, $value);
        }

        return new self($elements);
    }

    /**
     * Adds the cookies in the collection to response and returns it.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface response with added cookies.
     */
    public function addToResponse(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->cookies as $cookie) {
            $response = $cookie->addToResponse($response);
        }

        return $response;
    }

    /**
     * Creates a copy of the response with cookies set from the collection.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface response with new cookies.
     */
    public function setToResponse(ResponseInterface $response): ResponseInterface
    {
        $response = $response->withoutHeader('Set-Cookie');
        return $this->addToResponse($response);
    }

    /**
     * Populates the cookie collection from a ResponseInterface.
     *
     * @param ResponseInterface $response the response object to populate from
     * @return static collection created from response
     * @throws Exception
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        $collection = new self();
        foreach ($response->getHeader('Set-Cookie') as $setCookieString) {
            $cookie = Cookie::fromCookieString($setCookieString);
            $collection->add($cookie);
        }
        return $collection;
    }
}
