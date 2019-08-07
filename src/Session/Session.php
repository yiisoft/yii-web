<?php

namespace Yiisoft\Yii\Web\Session;

/**
 * Session provides session data management and the related configurations.
 */
class Session
{
    private const DEFAULT_OPTIONS = [
        'use_cookies' => 1,
        'cookie_secure' => 1,
        'use_only_cookies' => 1,
        'cookie_httponly' => 1,
        'strict_mode' => 1,
        'sid_bits_per_character' => 6,
        'sid_length' => 48,
        'cookie_samesite' => 'Lax',
        'cache_limiter' => 'nocache',
    ];

    private $options;

    public function __construct(array $options = [], \SessionHandlerInterface $handler = null)
    {
        if ($handler !== null) {
            session_set_save_handler($handler);
        }

        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);

        if ($this->isActive()) {
            throw new \RuntimeException('Session is already started');
        }
    }

    /**
     * Read value from session
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $this->open();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Write value into session
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        $this->open();
        $_SESSION[$key] = $value;
    }

    /**
     * Write session and close it
     */
    public function close(): void
    {
        if ($this->isActive()) {
            try {
                session_write_close();
            } catch (\Throwable $e) {
                throw new SessionException('Unable to close session', $e->getCode(), $e);
            }
        }
    }

    /**
     * Start sesion if it is not started yet
     */
    public function open(): void
    {
        if ($this->isActive()) {
            return;
        }

        try {
            session_start($this->options);
        } catch (\Throwable $e) {
            throw new SessionException('Failed to start session', $e->getCode(), $e);
        }
    }

    /**
     * @return bool if session is started
     */
    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * @return string|null current session ID or null if there is no started session
     */
    public function getId(): ?string
    {
        $id = session_id();
        return $id === '' ? null : $id;
    }

    /**
     * Regenerate session ID keeping data
     */
    public function regenerateId(): void
    {
        if ($this->isActive()) {
            try {
                session_regenerate_id();
            } catch (\Throwable $e) {
                throw new SessionException('Failed to regenerate ID', $e->getCode(), $e);
            }
        }
    }

    /**
     * Discard session changes and close session
     */
    public function discard(): void
    {
        if ($this->isActive()) {
            session_abort();
        }
    }

    /**
     * @return string session name
     */
    public function getName(): string
    {
        return session_name();
    }

    /**
     * @return array all session data
     */
    public function all(): array
    {
        $this->open();
        return $_SESSION;
    }

    /**
     * Remove value from session
     *
     * @param string $key
     */
    public function remove(string $key): void
    {
        $this->open();
        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $value;
        }
        return null;
    }

    /**
     * Check if session has a value with a given key
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->open();
        return isset($_SESSION[$key]);
    }

    /**
     * Read value and remove it afterwards
     *
     * @param string $key
     * @return mixed
     */
    public function pull(string $key)
    {
        $value = $this->get($key);
        $this->remove($key);
        return $value;
    }

    /**
     * Remove session data from runtime
     */
    public function clear(): void
    {
        $this->open();
        $_SESSION = [];
    }

    /**
     * Remove session data from runtime and session storage
     */
    public function destroy(): void
    {
        if ($this->isActive()) {
            session_destroy();
        }
    }

    public function getCookieParameters(): array
    {
        return session_get_cookie_params();
    }
}
