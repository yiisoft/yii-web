<?php


namespace yii\web\tests\router;

use PHPUnit\Framework\TestCase;
use yii\web\router\Group;
use yii\web\router\NoRoute;
use yii\web\router\Route;
use yii\web\router\UrlGeneratorInterface;

class UrlGeneratorTest extends TestCase
{
    private function getGenerator(array $routes): UrlGeneratorInterface
    {
        return new Group($routes);
    }

    public function testMissingRoute()
    {
        $generator = $this->getGenerator([]);

        $this->expectException(NoRoute::class);
        $generator->generate('missing');
    }

    public function testGenerateStatic()
    {
        $handler = function () {
        };
        $generator = $this->getGenerator([
            Route::get('/home')->to($handler)->name('home')
        ]);

        $url = $generator->generate('home');

        $this->assertSame('/home', $url);
    }
}
