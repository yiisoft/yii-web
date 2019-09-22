<?php

namespace Yiisoft\Yii\Web;

use Yiisoft\Yii\Web\Session\SessionInterface;

final class Flash
{
    private const COUNTERS = '__counters';

    private const FLASH_PARAM = '__flash';

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Returns a flash message.
     * @param string $key the key identifying the flash message
     * @param mixed $defaultValue value to be returned if the flash message does not exist.
     * @param bool $delete whether to delete this flash message right after this method is called.
     * If false, the flash message will be automatically deleted in the next request.
     * @return mixed the flash message or an array of messages if addFlash was used
     */
    public function get(string $key, $defaultValue = null, bool $delete = false)
    {
        $flashes = $this->fetch();

        if (isset($flashes[$key], $flashes[self::COUNTERS][$key])) {
            $value = $flashes[$key];

            if ($delete) {
                $this->remove($key);
            } elseif ($flashes[self::COUNTERS][$key] < 0) {
                // mark for deletion in the next request
                $flashes[self::COUNTERS][$key] = 1;
                $this->save($flashes);
            }

            return $value;
        }

        return $defaultValue;
    }

    /**
     * Returns all flash messages.
     *
     * You may use this method to display all the flash messages in a view file:
     *
     * ```php
     * <?php
     * foreach ($flash->getAllFlashes() as $key => $message) {
     *     echo '<div class="alert alert-' . $key . '">' . $message . '</div>';
     * } ?>
     * ```
     *
     * With the above code you can use the [bootstrap alert][] classes such as `success`, `info`, `danger`
     * as the flash message key to influence the color of the div.
     *
     * Note that if you use [[addFlash()]], `$message` will be an array, and you will have to adjust the above code.
     *
     * [bootstrap alert]: http://getbootstrap.com/components/#alerts
     *
     * @param bool $delete whether to delete the flash messages right after this method is called.
     * If false, the flash messages will be automatically deleted in the next request.
     * @return array flash messages (key => message or key => [message1, message2]).
     */
    public function getAll(bool $delete = false): array
    {
        $flashes = $this->fetch();

        $list = [];
        foreach ($flashes as $key => $value) {
            if ($key === self::COUNTERS) {
                continue;
            }

            $list[$key] = $value;
            if ($delete) {
                unset($flashes[self::COUNTERS][$key], $flashes[$key]);
            } elseif ($flashes[self::COUNTERS][$key] < 0) {
                // mark for deletion in the next request
                $flashes[self::COUNTERS][$key] = 1;
            }
        }

        $this->save($flashes);

        return $list;
    }

    /**
     * Sets a flash message.
     * A flash message will be automatically deleted after it is accessed in a request and the deletion will happen
     * in the next request.
     * If there is already an existing flash message with the same key, it will be overwritten by the new one.
     * @param string $key the key identifying the flash message.
     * @param mixed $value flash message
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if
     * it is accessed. If false, the flash message will be automatically removed after the next request,
     * regardless if it is accessed or not. If true (default value), the flash message will remain until after
     * it is accessed.
     */
    public function set(string $key, $value = true, bool $removeAfterAccess = true): void
    {
        $flashes = $this->fetch();
        $flashes[self::COUNTERS][$key] = $removeAfterAccess ? -1 : 0;
        $flashes[$key] = $value;
        $this->save($flashes);
    }

    /**
     * Adds a flash message.
     * If there are existing flash messages with the same key, the new one will be appended to the existing message array.
     * @param string $key the key identifying the flash message.
     * @param mixed $value flash message
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if
     * it is accessed. If false, the flash message will be automatically removed after the next request,
     * regardless if it is accessed or not. If true (default value), the flash message will remain until after
     * it is accessed.
     */
    public function add(string $key, $value = true, bool $removeAfterAccess = true): void
    {
        $flashes = $this->fetch();
        $flashes[self::COUNTERS][$key] = $removeAfterAccess ? -1 : 0;

        if (empty($flashes[$key])) {
            $flashes[$key] = [$value];
        } elseif (is_array($flashes[$key])) {
            $flashes[$key][] = $value;
        } else {
            $flashes[$key] = [$flashes[$key], $value];
        }

        $this->save($flashes);
    }

    /**
     * Removes a flash message.
     * @param string $key the key identifying the flash message.
     * @return mixed the removed flash message. Null if the flash message does not exist.
     */
    public function remove(string $key)
    {
        $flashes = $this->fetch();

        $value = isset($flashes[$key], $flashes[self::COUNTERS][$key]) ? $flashes[$key] : null;
        unset($flashes[$key], $flashes[self::COUNTERS][$key]);

        $this->save($flashes);

        return $value;
    }

    /**
     * Removes all flash messages.
     */
    public function removeAll(): void
    {
        $this->save([self::COUNTERS => []]);
    }

    /**
     * Returns a value indicating whether there are flash messages associated with the specified key.
     * @param string $key key identifying the flash message type
     * @return bool whether any flash messages exist under specified key
     */
    public function has(string $key): bool
    {
        $flashes = $this->fetch();
        return isset($flashes[$key], $flashes[self::COUNTERS][$key]);
    }


    /**
     * Updates the counters for flash messages and removes outdated flash messages.
     * This method should be called once after session initialization.
     */
    private function updateCounters(): void
    {
        $flashes = $this->session->get(self::FLASH_PARAM, []);
        if (!is_array($flashes)) {
            $flashes = [self::COUNTERS => []];
        }

        $counters = $flashes[self::COUNTERS] ?? [];
        if (!is_array($counters)) {
            $counters = [];
        }


        foreach ($counters as $key => $count) {
            if ($count > 0) {
                unset($counters[$key], $flashes[$key]);
            } elseif ($count === 0) {
                $counters[$key]++;
            }
        }

        $flashes[self::COUNTERS] = $counters;
        $this->save($flashes);
    }

    private static $init;

    private function fetch(): array
    {
        // ensure session is active (and has id)
        $this->session->open();
        if (self::$init !== $this->session->getId()) {
            self::$init = $this->session->getId();
            $this->updateCounters();
        }

        return $this->session->get(self::FLASH_PARAM, []);
    }

    private function save(array $flashes): void
    {
        $this->session->set(self::FLASH_PARAM, $flashes);
    }
}
