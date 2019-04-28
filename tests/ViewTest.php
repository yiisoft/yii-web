<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests;

use Yiisoft\Cache\FileCache;
use yii\view\Theme;
use yii\web\Request;
use yii\web\View;
use yii\tests\TestCase;

/**
 * @group web
 */
class ViewTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
    }
    
    public function testRegisterJsVar()
    {
        $this->mockWebApplication([], null, [
            'request' => [
                '__class' => Request::class,
                'scriptFile' => __DIR__ . '/baseUrl/index.php',
                'scriptUrl' => '/baseUrl/index.php',
            ],
        ]);

        $view = $this->createView();
        $view->registerJsVar('username', 'samdark');
        $html = $view->render('@yii/tests/data/views/layout.php', ['content' => 'content']);
        $this->assertContains('<script>var username = "samdark";</script></head>', $html);
        
        $view = $this->createView();
        $view->registerJsVar('objectTest', [
            'number' => 42,
            'question' => 'Unknown',
        ]);
        $html = $view->render('@yii/tests/data/views/layout.php', ['content' => 'content']);
        $this->assertContains('<script>var objectTest = {"number":42,"question":"Unknown"};</script></head>', $html);
    }

    public function testRegisterJsFileWithAlias()
    {
        $this->mockWebApplication([], null, [
            'request' => [
                '__class' => Request::class,
                'scriptFile' => __DIR__ . '/baseUrl/index.php',
                'scriptUrl' => '/baseUrl/index.php',
            ],
        ]);

        $view = $this->createView();
        $view->registerJsFile('@web/js/somefile.js', ['position' => View::POS_HEAD]);
        $html = $view->render('@yii/tests/data/views/layout.php', ['content' => 'content']);
        $this->assertContains('<script src="/baseUrl/js/somefile.js"></script></head>', $html);

        $view = $this->createView();
        $view->registerJsFile('@web/js/somefile.js', ['position' => View::POS_BEGIN]);
        $html = $view->render('@yii/tests/data/views/layout.php', ['content' => 'content']);
        $this->assertContains('<body>' . PHP_EOL . '<script src="/baseUrl/js/somefile.js"></script>', $html);

        $view = $this->createView();
        $view->registerJsFile('@web/js/somefile.js', ['position' => View::POS_END]);
        $html = $view->render('@yii/tests/data/views/layout.php', ['content' => 'content']);
        $this->assertContains('<script src="/baseUrl/js/somefile.js"></script></body>', $html);
    }

    public function testRegisterCssFileWithAlias()
    {
        $this->mockWebApplication([], null, [
            'request' => [
                '__class' => Request::class,
                'scriptFile' => __DIR__ . '/baseUrl/index.php',
                'scriptUrl' => '/baseUrl/index.php',
            ],
        ]);

        $view = $this->createView();
        $view->registerCssFile('@web/css/somefile.css');
        $html = $view->render('@yii/tests/data/views/layout.php', ['content' => 'content']);
        $this->assertContains('<link href="/baseUrl/css/somefile.css" rel="stylesheet"></head>', $html);
    }

    public function testRegisterCsrfMetaTags()
    {
        $this->mockWebApplication([], null, [
            'request' => [
                '__class' => Request::class,
                'cookieValidationKey' => 'secretkey',
                'scriptFile' => __DIR__ . '/baseUrl/index.php',
                'scriptUrl' => '/baseUrl/index.php',
            ],
            'cache' => [
                '__class' => FileCache::class,
            ],
        ]);

        $view = $this->createView();

        $view->registerCsrfMetaTags();
        $html = $view->render('@yii/tests/data/views/layout.php', ['content' => 'content']);
        $this->assertContains('<meta name="csrf-param" content="_csrf">', $html);
        $this->assertContains('<meta name="csrf-token" content="', $html);
        $csrfToken1 = $this->getCSRFTokenValue($html);

        // regenerate token
        $this->app->request->getCsrfToken(true);
        $view->registerCsrfMetaTags();
        $html = $view->render('@yii/tests/data/views/layout.php', ['content' => 'content']);
        $this->assertContains('<meta name="csrf-param" content="_csrf">', $html);
        $this->assertContains('<meta name="csrf-token" content="', $html);
        $csrfToken2 = $this->getCSRFTokenValue($html);

        $this->assertNotSame($csrfToken1, $csrfToken2);
    }

    /**
     * Parses CSRF token from page HTML.
     *
     * @param string $html
     * @return string CSRF token
     */
    private function getCSRFTokenValue($html)
    {
        if (!preg_match('~<meta name="csrf-token" content="([^"]+)">~', $html, $matches)) {
            $this->fail("No CSRF-token meta tag found. HTML was:\n$html");
        }

        return $matches[1];
    }

    private function createView()
    {
        return new View($this->app, new Theme());
    }
}
