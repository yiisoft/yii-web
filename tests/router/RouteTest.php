<?php


namespace yii\web\tests\router;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use yii\web\router\Match;
use yii\web\router\NoHandler;
use yii\web\router\Route;


class RouteTest extends TestCase
{
    public function testMethodMismatch()
    {
        $request = new ServerRequest('GET', '/');

        $result = Route::post('/')->match($request);

        $this->assertNull($result);
    }

    public function testHostMismatch()
    {
        $request = new ServerRequest('GET', '/');
        $uri = $request->getUri();
        $uri = $uri->withHost('https://example.com/');
        $request = $request->withUri($uri);

        $result = Route::get('/')->host('https://yiiframework.com/')->match($request);

        $this->assertNull($result);
    }

    public function testMatchWithNoHandler()
    {
        $this->expectException(NoHandler::class);

        $request = new ServerRequest('GET', '/');
        Route::get('/')->match($request);
    }

    public function testStaticMatch()
    {
        $request = new ServerRequest('GET', '/');
        $result = Route::get('/')->to(function() {})->match($request);

        $this->assertInstanceOf(Match::class, $result);
    }


}
