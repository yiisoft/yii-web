<?php
namespace Yiisoft\Yii\Web;

final class Info
{
    /**
     * @return string
     */
    public static function frameworkVersion(): string
    {
        return '3.0.0';
    }

    /**
     * @return string
     */
    public static function frameworkPath(): string
    {
        return dirname(__DIR__, 2);
    }
}
