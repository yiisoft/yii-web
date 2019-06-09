<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Web\Tests\Filters;

use yii\base\Action;
use Yiisoft\Web\Filters\ContentNegotiator;
use Yiisoft\Web\Controller;
use Yiisoft\Web\Request;
use Yiisoft\Web\Response;
use yii\tests\TestCase;

/**
 *  @group filters
 */
class ContentNegotiatorTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->mockWebApplication();
    }

    protected function mockActionAndFilter()
    {
        $action = new Action('test', new Controller('id', $this->app));
        $filter = new ContentNegotiator([
            'request' => new Request($this->app),
            'response' => new Response($this->app),
        ]);

        return [$action, $filter];
    }

    public function testWhenLanguageGETParamIsArray()
    {
        list($action, $filter) = $this->mockActionAndFilter();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[$filter->languageParam] = [
            'foo',
            'string-index' => 'bar',
        ];

        $targetLanguage = 'de';
        $filter->languages = [$targetLanguage, 'ru', 'en'];

        $filter->beforeAction($action);
        $this->assertEquals($targetLanguage, $this->app->language);
    }

    /**
     * @expectedException Yiisoft\Web\BadRequestHttpException
     * @expectedExceptionMessageRegExp |Invalid data received for GET parameter '.+'|
     */
    public function testWhenFormatGETParamIsArray()
    {
        list($action, $filter) = $this->mockActionAndFilter();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[$filter->formatParam] = [
            'format-A',
            'string-index' => 'format-B',
        ];

        $filter->formats = [
            'application/json' => Response::FORMAT_JSON,
            'application/xml' => Response::FORMAT_XML,
        ];

        $filter->beforeAction($action);
    }
}
