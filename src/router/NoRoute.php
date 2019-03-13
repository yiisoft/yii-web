<?php

namespace yii\router;

class NoRoute extends \Exception
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
        parent::__construct("There is no route named \"$name\"");
    }

    public function getName(): string
    {
        return $this->name;
    }
}
