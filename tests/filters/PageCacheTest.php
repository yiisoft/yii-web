<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests\filters;

use yii\helpers\Yii;
use yii\base\Action;
use yii\cache\ArrayCache;
use yii\cache\Cache;
use yii\cache\dependencies\ExpressionDependency;
use yii\web\filters\PageCache;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\Controller;
use yii\http\Cookie;
use yii\view\Theme;
use yii\web\View;
use yii\cache\tests\unit\CacheTestCase;
use yii\tests\TestCase;

/**
 * @group filters
 */
class PageCacheTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();
        $_SERVER['SCRIPT_FILENAME'] = '/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    protected function tearDown()
    {
        CacheTestCase::$time = null;
        CacheTestCase::$microtime = null;
    }

    public function cacheTestCaseProvider()
    {
        return [
            // Basic
            [[
                'name' => 'disabled',
                'properties' => [
                    'enabled' => false,
                ],
                'cacheable' => false,
            ]],
            [[
                'name' => 'simple',
            ]],

            // Cookies
            [[
                'name' => 'allCookies',
                'properties' => [
                    'cacheCookies' => true,
                ],
                'cookies' => [
                    'test-cookie-1' => true,
                    'test-cookie-2' => true,
                ],
            ]],
            [[
                'name' => 'someCookies',
                'properties' => [
                    'cacheCookies' => ['test-cookie-2'],
                ],
                'cookies' => [
                    'test-cookie-1' => false,
                    'test-cookie-2' => true,
                ],
            ]],
            [[
                'name' => 'noCookies',
                'properties' => [
                    'cacheCookies' => false,
                ],
                'cookies' => [
                    'test-cookie-1' => false,
                    'test-cookie-2' => false,
                ],
            ]],

            // Headers
            [[
                'name' => 'allHeaders',
                'properties' => [
                    'cacheHeaders' => true,
                ],
                'headers' => [
                    'test-header-1' => true,
                    'test-header-2' => true,
                ],
            ]],
            [[
                'name' => 'someHeaders',
                'properties' => [
                    'cacheHeaders' => ['test-header-2'],
                ],
                'headers' => [
                    'test-header-1' => false,
                    'test-header-2' => true,
                ],
            ]],
            [[
                'name' => 'noHeaders',
                'properties' => [
                    'cacheHeaders' => false,
                ],
                'headers' => [
                    'test-header-1' => false,
                    'test-header-2' => false,
                ],
            ]],

            // All together
            [[
                'name' => 'someCookiesSomeHeaders',
                'properties' => [
                    'cacheCookies' => ['test-cookie-2'],
                    'cacheHeaders' => ['test-header-2'],
                ],
                'cookies' => [
                    'test-cookie-1' => false,
                    'test-cookie-2' => true,
                ],
                'headers' => [
                    'test-header-1' => false,
                    'test-header-2' => true,
                ],
            ]],
        ];
    }

    /**
     * @dataProvider cacheTestCaseProvider
     * @param array $testCase
     */
    public function testCache($testCase)
    {
        $testCase = ArrayHelper::merge([
            'properties' => [],
            'cacheable' => true,
        ], $testCase);
        if (isset($this->app)) {
            $this->destroyApplication();
        }
        // Prepares the test response
        $this->mockWebApplication();
        $controller = new Controller('test', $this->app);
        $action = new Action('test', $controller);
        $filter = $this->factory->create(array_merge([
            '__class' => PageCache::class,
            'cache' => $cache = new Cache(new ArrayCache()),
            'view' => $this->createView(),
        ], $testCase['properties']));
        $this->assertTrue($filter->beforeAction($action), $testCase['name']);
        // Cookies
        $cookies = [];
        if (isset($testCase['cookies'])) {
            foreach (array_keys($testCase['cookies']) as $name) {
                $value = $this->app->security->generateRandomString();
                $this->app->response->cookies->add(new Cookie([
                    'name' => $name,
                    'value' => $value,
                    'expire' => strtotime('now +1 year'),
                ]));
                $cookies[$name] = $value;
            }
        }
        // Headers
        $headers = [];
        if (isset($testCase['headers'])) {
            foreach (array_keys($testCase['headers']) as $name) {
                $value = $this->app->security->generateRandomString();
                $this->app->response->addHeader($name, $value);
                $headers[$name] = $value;
            }
        }
        // Content
        $static = $this->app->security->generateRandomString();
        $this->app->params['dynamic'] = $dynamic = $this->app->security->generateRandomString();
        $content = $filter->view->render('@yii/tests/data/views/pageCacheLayout.php', ['static' => $static]);
        $this->app->response->content = $content;
        ob_start();
        $this->app->response->send();
        ob_end_clean();
        // Metadata
        $metadata = [
            'format' => $this->app->response->format,
            'protocolVersion' => $this->app->response->getProtocolVersion(),
            'statusCode' => $this->app->response->getStatusCode(),
            'reasonPhrase' => $this->app->response->getReasonPhrase(),
        ];
        if ($testCase['cacheable']) {
            $this->assertNotEmpty($this->getInaccessibleProperty($filter->cache->handler, '_cache'), $testCase['name']);
        } else {
            $this->assertEmpty($this->getInaccessibleProperty($filter->cache->handler, '_cache'), $testCase['name']);
            return;
        }

        // Verifies the cached response
        $this->destroyApplication();
        $this->mockWebApplication();
        $controller = new Controller('test', $this->app);
        $action = new Action('test', $controller);
        $filter = $this->factory->create(array_merge([
            '__class' => PageCache::class,
            'cache' => $cache,
            'view' => $this->createView(),
        ]), $testCase['properties']);
        $this->app->params['dynamic'] = $dynamic = $this->app->security->generateRandomString();
        $this->assertFalse($filter->beforeAction($action), $testCase['name']);
        // Content
        $json = Json::decode($this->app->response->content);
        $this->assertSame($static, $json['static'], $testCase['name']);
        $this->assertSame($dynamic, $json['dynamic'], $testCase['name']);
        // Metadata
        $this->assertSame($metadata['format'], $this->app->response->format, $testCase['name']);
        $this->assertSame($metadata['protocolVersion'], $this->app->response->getProtocolVersion(), $testCase['name']);
        $this->assertSame($metadata['statusCode'], $this->app->response->getStatusCode(), $testCase['name']);
        $this->assertSame($metadata['reasonPhrase'], $this->app->response->getReasonPhrase(), $testCase['name']);
        // Cookies
        if (isset($testCase['cookies'])) {
            foreach ($testCase['cookies'] as $name => $expected) {
                $this->assertSame($expected, $this->app->response->cookies->has($name), $testCase['name']);
                if ($expected) {
                    $this->assertSame($cookies[$name], $this->app->response->cookies->getValue($name), $testCase['name']);
                }
            }
        }
        // Headers
        if (isset($testCase['headers'])) {
            foreach ($testCase['headers'] as $name => $expected) {
                $this->assertSame($expected, $this->app->response->hasHeader($name), $testCase['name']);
                if ($expected) {
                    $this->assertSame($headers[$name], $this->app->response->getHeaderLine($name), $testCase['name']);
                }
            }
        }
    }

    public function testExpired()
    {
        CacheTestCase::$time = time();
        CacheTestCase::$microtime = microtime(true);

        // Prepares the test response
        $this->mockWebApplication();
        $controller = new Controller('test', $this->app);
        $action = new Action('test', $controller);
        $filter = $this->factory->create([
            '__class' => PageCache::class,
            'cache' => $cache = new Cache(new ArrayCache()),
            'view' => $this->createView(),
            'duration' => 1,
        ]);
        $this->assertTrue($filter->beforeAction($action));
        $static = $this->app->security->generateRandomString();
        $this->app->params['dynamic'] = $dynamic = $this->app->security->generateRandomString();
        $content = $filter->view->render('@yii/tests/data/views/pageCacheLayout.php', ['static' => $static]);
        $this->app->response->content = $content;
        ob_start();
        $this->app->response->send();
        ob_end_clean();

        $this->assertNotEmpty($this->getInaccessibleProperty($filter->cache->handler, '_cache'));

        // mock sleep(2);
        CacheTestCase::$time += 2;
        CacheTestCase::$microtime += 2;

        // Verifies the cached response
        $this->destroyApplication();
        $this->mockWebApplication();
        $controller = new Controller('test', $this->app);
        $action = new Action('test', $controller);
        $filter = $this->factory->create([
            '__class' => PageCache::class,
            'cache' => $cache,
            'view' => $this->createView(),
        ]);
        $this->app->params['dynamic'] = $dynamic = $this->app->security->generateRandomString();
        $this->assertTrue($filter->beforeAction($action));
        ob_start();
        $this->app->response->send();
        ob_end_clean();
    }

    public function testVaryByRoute()
    {
        $testCases = [
            false,
            true,
        ];

        foreach ($testCases as $enabled) {
            if (isset($this->app)) {
                $this->destroyApplication();
            }
            // Prepares the test response
            $this->mockWebApplication();
            $controller = new Controller('test', $this->app);
            $action = new Action('test', $controller);
            $this->app->requestedRoute = $action->uniqueId;
            $filter = $this->factory->create([
                '__class' => PageCache::class,
                'cache' => $cache = new Cache(new ArrayCache()),
                'view' => $this->createView(),
                'varyByRoute' => $enabled,
            ]);
            $this->assertTrue($filter->beforeAction($action));
            $static = $this->app->security->generateRandomString();
            $this->app->params['dynamic'] = $dynamic = $this->app->security->generateRandomString();
            $content = $filter->view->render('@yii/tests/data/views/pageCacheLayout.php', ['static' => $static]);
            $this->app->response->content = $content;
            ob_start();
            $this->app->response->send();
            ob_end_clean();

            $this->assertNotEmpty($this->getInaccessibleProperty($filter->cache->handler, '_cache'));

            // Verifies the cached response
            $this->destroyApplication();
            $this->mockWebApplication();
            $controller = new Controller('test', $this->app);
            $action = new Action('test2', $controller);
            $this->app->requestedRoute = $action->uniqueId;
            $filter = $this->factory->create([
                '__class' => PageCache::class,
                'cache' => $cache,
                'view' => $this->createView(),
                'varyByRoute' => $enabled,
            ]);
            $this->app->params['dynamic'] = $dynamic = $this->app->security->generateRandomString();
            $this->assertSame($enabled, $filter->beforeAction($action), $enabled);
            ob_start();
            $this->app->response->send();
            ob_end_clean();
        }
    }

    public function testVariations()
    {
        $testCases = [
            [true, 'name' => 'value'],
            [false, 'name' => 'value2'],
        ];

        foreach ($testCases as $testCase) {
            if (isset($this->app)) {
                $this->destroyApplication();
            }
            $expected = array_shift($testCase);
            // Prepares the test response
            $this->mockWebApplication();
            $controller = new Controller('test', $this->app);
            $action = new Action('test', $controller);
            $originalVariations = $testCases[0];
            array_shift($originalVariations);
            $filter = $this->factory->create([
                '__class' => PageCache::class,
                'cache' => $cache = new Cache(new ArrayCache()),
                'view' => $this->createView(),
                'variations' => $originalVariations,
            ]);
            $this->assertTrue($filter->beforeAction($action));
            $static = $this->app->security->generateRandomString();
            $this->app->params['dynamic'] = $dynamic = $this->app->security->generateRandomString();
            $content = $filter->view->render('@yii/tests/data/views/pageCacheLayout.php', ['static' => $static]);
            $this->app->response->content = $content;
            ob_start();
            $this->app->response->send();
            ob_end_clean();

            $this->assertNotEmpty($this->getInaccessibleProperty($filter->cache->handler, '_cache'));

            // Verifies the cached response
            $this->destroyApplication();
            $this->mockWebApplication();
            $controller = new Controller('test', $this->app);
            $action = new Action('test', $controller);
            $filter = $this->factory->create([
                '__class' => PageCache::class,
                'cache' => $cache,
                'view' => $this->createView(),
                'variations' => $testCase,
            ]);
            $this->app->params['dynamic'] = $dynamic = $this->app->security->generateRandomString();
            $this->assertNotSame($expected, $filter->beforeAction($action), $expected);
            ob_start();
            $this->app->response->send();
            ob_end_clean();
        }
    }

    public function testDependency()
    {
        $testCases = [
            false,
            true,
        ];

        foreach ($testCases as $changed) {
            if (isset($this->app)) {
                $this->destroyApplication();
            }
            // Prepares the test response
            $this->mockWebApplication();
            $controller = new Controller('test', $this->app);
            $action = new Action('test', $controller);
            $filter = $this->factory->create([
                '__class' => PageCache::class,
                'cache' => $cache = new Cache(new ArrayCache()),
                'view' => $this->createView(),
                'dependency' => [
                    '__class' => ExpressionDependency::class,
                    'expression' => '$this->app->params[\'dependency\']',
                ],
            ]);
            $this->assertTrue($filter->beforeAction($action));
            $static = $this->app->security->generateRandomString();
            $this->app->params['dynamic'] = $dynamic = $this->app->security->generateRandomString();
            $this->app->params['dependency'] = $dependency = $this->app->security->generateRandomString();
            $content = $filter->view->render('@yii/tests/data/views/pageCacheLayout.php', ['static' => $static]);
            $this->app->response->content = $content;
            ob_start();
            $this->app->response->send();
            ob_end_clean();

            $this->assertNotEmpty($this->getInaccessibleProperty($filter->cache->handler, '_cache'));

            // Verifies the cached response
            $this->destroyApplication();
            $this->mockWebApplication();
            $controller = new Controller('test', $this->app);
            $action = new Action('test', $controller);
            $filter = $this->factory->create([
                '__class' => PageCache::class,
                'cache' => $cache,
                'view' => $this->createView(),
            ]);
            $this->app->params['dynamic'] = $dynamic = $this->app->security->generateRandomString();
            if ($changed) {
                $this->app->params['dependency'] = $this->app->security->generateRandomString();
            } else {
                $this->app->params['dependency'] = $dependency;
            }
            $this->assertSame($changed, $filter->beforeAction($action), $changed);
            ob_start();
            $this->app->response->send();
            ob_end_clean();
        }
    }

    public function testCalculateCacheKey()
    {
        $expected = [PageCache::class, 'test', 'ru'];
        $this->app->requestedRoute = 'test';
        $keys = $this->invokeMethod(new PageCache(['variations' => ['ru']]), 'calculateCacheKey');
        $this->assertEquals($expected, $keys);

        $keys = $this->invokeMethod(new PageCache(['variations' => 'ru']), 'calculateCacheKey');
        $this->assertEquals($expected, $keys);

        $keys = $this->invokeMethod(new PageCache(), 'calculateCacheKey');
        $this->assertEquals([PageCache::class, 'test'], $keys);
    }

    protected function createView()
    {
        return new View($this->app, new Theme());
    }
}
