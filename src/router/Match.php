<?php


namespace yii\web\router;


class Match
{
    private $name;
    private $parameters;
    private $callback;

    public function __construct(callable $callback, array $parameters = [], ?string $name = null)
    {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->callback = $callback;
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
    public function getCallback(): callable
    {
        return $this->callback;
    }
}
