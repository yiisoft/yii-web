<?php

namespace yii\web\router;


interface UrlGeneratorInterface
{
    public const TYPE_ABSOLUTE = 'absolute';
    public const TYPE_RELATIVE = 'relative';

    public function generate(string $name, array $parameters = [], string $type = self::TYPE_ABSOLUTE): string;
}
