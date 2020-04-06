<?php

namespace Yiisoft\Yii\Web\Tests\Config;

class Event
{
    /**
     * @var string[]
     */
    private array $registered = [];

    public function register($value): void
    {
        $this->registered[] = $value;
    }

    /**
     * @return string[]
     */
    public function registered(): array
    {
        return $this->registered;
    }
}
