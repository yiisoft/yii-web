<?php


namespace yii\web\tests\router;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use yii\web\router\Group;
use yii\web\router\NoRoute;
use yii\web\router\Route;
use yii\web\router\UrlGeneratorInterface;

class UrlGeneratorTest extends TestCase
{
    private function getRequest(): ServerRequestInterface
    {
        return new ServerRequest('GET', 'https://example.com/');
    }

    private function getGenerator(array $routes): UrlGeneratorInterface
    {
        return new Group($routes);
    }

    public function testMissingRoute()
    {
        $generator = $this->getGenerator([]);

        $this->expectException(NoRoute::class);
        $generator->generate('missing', $this->getRequest());
    }

    public function testStatic()
    {
        $handler = function () {
            // does not matter
        };
        $generator = $this->getGenerator([
            Route::get('/home')->to($handler)->name('home')
        ]);

        $url = $generator->generate('home', $this->getRequest());

        $this->assertSame('https://example.com/home', $url);
    }

    public function testSimpleParameter()
    {
        $handler = function () {
            // does not matter
        };
        $generator = $this->getGenerator([
            Route::get('/post/view/<id>')
                ->parameters([
                    'id' => '\d+'
                ])
                ->to($handler)
                ->name('post/view')
        ]);

        $url = $generator->generate('post/view', $this->getRequest(), ['id' => 1]);

        $this->assertSame('https://example.com/post/view/1', $url);
    }

    public function testAdditionalParameter()
    {
        $handler = function () {
            // does not matter
        };
        $generator = $this->getGenerator([
            Route::get('/post/view/<id>')
                ->parameters([
                    'id' => '\d+'
                ])
                ->to($handler)
                ->name('post/view')
        ]);

        $url = $generator->generate('post/view', $this->getRequest(), ['id' => 1, 'referral' => 'samdark']);

        $this->assertSame('https://example.com/post/view/1?referral=samdark', $url);
    }

    public function testHash()
    {
        $handler = function () {
            // does not matter
        };
        $generator = $this->getGenerator([
            Route::get('/posts')
                ->to($handler)
                ->name('post/list')
        ]);

        $url = $generator->generate('post/list', $this->getRequest(), ['#' => '2019']);

        $this->assertSame('https://example.com/posts#2019', $url);
    }

    public function testParameterEncoding()
    {
        $handler = function () {
            // does not matter
        };
        $generator = $this->getGenerator([
            Route::get('/search/<query>')
                ->to($handler)
                ->name('post/search')
        ]);

        $url = $generator->generate('post/search', $this->getRequest(), ['query' => 'sample post']);

        $this->assertSame('https://example.com/post/view/sample+post', $url);
    }

    public function testsDefaultParameters()
    {
        $handler = function () {
            // does not matter
        };
        $generator = $this->getGenerator([
            Route::get('/archive/<year>')
                ->to($handler)
                ->defaults([
                    'year' => 2019,
                ])
                ->name('post/archive')
        ]);

        $url = $generator->generate('post/archive', $this->getRequest());

        $this->assertSame('https://example.com/archive/2019', $url);
    }

    public function testDefaultParametersOverride()
    {
        $handler = function () {
            // does not matter
        };
        $generator = $this->getGenerator([
            Route::get('/archive/<year>')
                ->to($handler)
                ->defaults([
                    'year' => 2019,
                ])
                ->name('post/archive')
        ]);

        $url = $generator->generate('post/archive', $this->getRequest(), ['year' => 2018]);

        $this->assertSame('https://example.com/archive/2018', $url);
    }

    public function testRuleHost()
    {
        $handler = function () {
            // does not matter
        };
        $generator = $this->getGenerator([
            Route::get('/')
                ->to($handler)
                ->host('https://www.yiiframework.com')
                ->name('yiiframework')
        ]);

        $url = $generator->generate('yiiframework', $this->getRequest());

        $this->assertSame('https://www.yiiframework.com/', $url);
    }
}
