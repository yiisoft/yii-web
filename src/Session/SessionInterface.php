<?php
namespace Yiisoft\Yii\Web\Session;

interface SessionInterface
{
    /**
     * Destroy session
     */
    public function destroy(): void;

    /**
     * Start sesion if it is not started yet
     */
    public function start(): void;

    /**
     * @return bool if session is started
     */
    public function isStarted(): bool;

    /**
     * @return string|null current session ID or null if there is no started session
     */
    public function getId(): ?string;

    /**
     * Regenerate session ID keeping data
     */
    public function renew(): void;

    /**
     * Write session into storage
     *
     * @return bool if data was written successfully
     */
    public function commit(): bool;

    /**
     * Discard session changes and close session
     */
    public function discard(): void;

    /**
     * @return string session name
     */
    public function getName(): string;

    /**
     * Set session name
     *
     * @param string $name new session name
     */
    public function setName(string $name): void;

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
     * Remove all session data
     */
    public function clear(): void;
}
