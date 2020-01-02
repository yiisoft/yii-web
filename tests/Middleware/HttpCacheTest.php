<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Method;
use Yiisoft\Yii\Web\Middleware\HttpCache;

class HttpCacheTest extends TestCase
{
    private function createMiddleware()
    {
        return new HttpCache(new Response(200));
    }

    private function createServerRequest(string $method = Method::GET, $headers = [])
    {
        return new ServerRequest($method, '/', $headers);
    }
}
