<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests;

use yii\web\NotFoundHttpException;
use yii\tests\TestCase;

class ErrorHandlerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockWebApplication([], null, [
            'errorHandler' => [
                '__class' => \yii\web\tests\ErrorHandler::class,
                'errorView' => '@yii/tests/data/views/errorHandler.php',
            ],
        ]);
    }

    public function testCorrectResponseCodeInErrorView()
    {
        /** @var ErrorHandler $handler */
        $handler = $this->app->getErrorHandler();
        ob_start(); // suppress response output
        $this->invokeMethod($handler, 'renderException', [new NotFoundHttpException('This message is displayed to end user')]);
        ob_get_clean();
        $out = $this->app->response->data;
        $this->assertEquals('Code: 404
Message: This message is displayed to end user
Exception: yii\web\NotFoundHttpException', $out);
    }

    public function testRenderCallStackItem()
    {
        $handler = $this->app->getErrorHandler();
        $handler->traceLine = '<a href="netbeans://open?file={file}&line={line}">{html}</a>';
        $file = $this->app->getAlias('@yii/web/Application.php');

        $out = $handler->renderCallStackItem($file, 63, \yii\web\Application::class, null, null, null);

        $this->assertContains('<a href="netbeans://open?file=' . $file . '&line=63">', $out);
    }
}

class ErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * @return bool if simple HTML should be rendered
     */
    protected function shouldRenderSimpleHtml()
    {
        return false;
    }
}
