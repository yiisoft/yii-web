<?php

namespace yii\web\router;

final class NewInstance
{
    private $class;
    private $method;

    private function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    public function __invoke(...$arguments)
    {
        $controller = new $this->class();
        $controller->{$this->method}(...$arguments);
    }
}
