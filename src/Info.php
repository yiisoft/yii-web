<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web;

final class Info
{
    public static function frameworkVersion(): string
    {
        return '3.0.0';
    }

    public static function frameworkPath(): string
    {
        return dirname(__DIR__, 2);
    }
}
