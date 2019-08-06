<?php
namespace Yiisoft\Yii\Web\Session;

class PhpSession implements SessionInterface
{
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }
}
