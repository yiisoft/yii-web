<?php
namespace Yiisoft\Yii\Web;

final class Info
{
    public static function getVersion(): string
    {
        return '3.0.0';
    }

    public static function getFrameworkPath(): string
    {
        return dirname(__DIR__, 2);
    }
}
