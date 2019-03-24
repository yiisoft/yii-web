<?php

namespace yii\web\tests\router;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use yii\web\router\Group;
use yii\web\router\NoHandler;
use yii\web\router\NoMatch;
use yii\web\router\Route;

class GroupTest extends TestCase
{
    public function testMethodMismatch()
    {
        $request = new ServerRequest('GET', '/');

        $group = new Group([
            Route::post('/'),
        ]);

        $this->expectException(NoMatch::class);
        $group->match($request);
    }

    public function testHostMismatch()
    {
        $request = new ServerRequest('GET', '/');
        $uri = $request->getUri();
        $uri = $uri->withHost('https://example.com/');
        $request = $request->withUri($uri);

        $group = new Group([
            Route::get('/')->host('https://yiiframework.com/'),
        ]);

        $this->expectException(NoMatch::class);
        $group->match($request);
    }

    public function testMatchWithNoHandler()
    {
        $request = new ServerRequest('GET', '/');
        $group = new Group([
            Route::get('/'),
        ]);

        $this->expectException(NoHandler::class);
        $group->match($request);
    }

    public function testStaticMatch()
    {
        $handler = function () {
        };
        $request = new ServerRequest('GET', '/');
        $group = new Group([
            Route::get('/')->to($handler),
        ]);

        $match = $group->match($request);
        $this->assertSame($handler, $match->getHandler());
        $this->assertEmpty($match->getParameters());
        $this->assertNull($match->getName());
    }
}
