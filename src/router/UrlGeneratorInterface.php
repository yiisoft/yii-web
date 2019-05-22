<?php

namespace yii\web\router;

interface UrlGeneratorInterface
{
    public function generate(string $name, array $parameters = []): string;
}
