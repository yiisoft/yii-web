<?php

namespace Yiisoft\Yii\Web\Session;

/**
 * Session provides session data management and the related configurations.
 */
class Session implements SessionInterface
{
    private $sessionId;

    private const DEFAULT_OPTIONS = [
        'use_cookies' => 1,
        'cookie_secure' => 1,
        'use_only_cookies' => 1,
        'cookie_httponly' => 1,
        'use_strict_mode' => 1,
        'sid_bits_per_character' => 6,
        'sid_length' => 48,
        'cache_limiter' => 'nocache',
        'cookie_samesite' => 'Lax',
    ];

    private $options;

    public function __construct(array $options = [], \SessionHandlerInterface $handler = null)
    {
        if ($handler !== null) {
            session_set_save_handler($handler);
        }

        $defaultOptions = self::DEFAULT_OPTIONS;
        $this->options = array_merge($defaultOptions, $options);
    }

    public function get(string $key, $default = null)
    {
        $this->open();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->open();
        $_SESSION[$key] = $value;
    }

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

    public function open(): void
    {
        if ($this->isActive()) {
            return;
        }

        if ($this->sessionId !== null) {
            session_id($this->sessionId);
        }

        try {
            session_start($this->options);
            $this->sessionId = session_id();
        } catch (\Throwable $e) {
            throw new SessionException('Failed to start session', $e->getCode(), $e);
        }
    }

    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function getId(): ?string
    {
        return $this->sessionId === '' ? null : $this->sessionId;
    }

    public function regenerateId(): void
    {
        if ($this->isActive()) {
            try {
                if (session_regenerate_id(true)) {
                    $this->sessionId = session_id();
                }
            } catch (\Throwable $e) {
                throw new SessionException('Failed to regenerate ID', $e->getCode(), $e);
            }
        }
    }

    public function discard(): void
    {
        if ($this->isActive()) {
            session_abort();
        }
    }

    public function getName(): string
    {
        return session_name();
    }

    public function all(): array
    {
        $this->open();
        return $_SESSION;
    }

    public function remove(string $key): void
    {
        $this->open();
        unset($_SESSION[$key]);
    }

    public function has(string $key): bool
    {
        $this->open();
        return isset($_SESSION[$key]);
    }

    public function pull(string $key)
    {
        $value = $this->get($key);
        $this->remove($key);
        return $value;
    }

    public function clear(): void
    {
        $this->open();
        $_SESSION = [];
    }

    public function destroy(): void
    {
        if ($this->isActive()) {
            session_destroy();
            $this->sessionId = null;
        }
    }

    public function getCookieParameters(): array
    {
        return session_get_cookie_params();
    }

    public function setId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }
}
