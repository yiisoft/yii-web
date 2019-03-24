<?php

namespace yii\web\router;

class Match
{
    private $name;
    private $parameters;
    private $handler;

    public function __construct(callable $handler, array $parameters = [], ?string $name = null)
    {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->handler = $handler;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return callable
     */
    public function getHandler(): callable
    {
        return $this->handler;
    }
}
