<?php

namespace Yiisoft\Yii\Web\Session;

interface SessionInterface
{
    /**
     * Read value from session
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Write value into session
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value): void;

    /**
     * Write session and close it
     */
    public function close(): void;

    /**
     * Start sesion if it is not started yet
     */
    public function open(): void;

    /**
     * @return bool if session is started
     */
    public function isActive(): bool;

    /**
     * @return string|null current session ID or null if there is no started session
     */
    public function getId(): ?string;

    /**
     * Regenerate session ID keeping data
     */
    public function regenerateId(): void;

    /**
     * Discard session changes and close session
     */
    public function discard(): void;

    /**
     * @return string session name
     */
    public function getName(): string;

    /**
     * @return array all session data
     */
    public function all(): array;

    /**
     * Remove value from session
     *
     * @param string $key
     */
    public function remove(string $key): void;

    /**
     * Check if session has a value with a given key
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Read value and remove it afterwards
     *
     * @param string $key
     * @return mixed
     */
    public function pull(string $key);

    /**
     * Remove session data from runtime
     */
    public function clear(): void;

    /**
     * Remove session data from runtime and session storage
     */
    public function destroy(): void;

    public function getCookieParameters(): array;
}
