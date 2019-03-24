<?php

namespace yii\web\router;

final class Method
{
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';
    public const PATCH = 'PATCH';
    public const HEAD = 'HEAD';
    public const OPTIONS = 'OPTIONS';

    public const ALL = [
        self::GET,
        self::POST,
        self::PUT,
        self::DELETE,
        self::PATCH,
        self::HEAD,
        self::OPTIONS,
    ];
}
