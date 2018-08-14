<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests;

use yii\helpers\Yii;
use yii\exceptions\InvalidConfigException;
use yii\exceptions\UserException;
use yii\tests\TestCase;
use yii\view\ViewNotFoundException;
use yii\web\Controller;
use yii\web\ErrorAction;

/**
 * @group web
 */
class ErrorActionTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockWebApplication();
    }

    /**
     * Creates a controller instance.
     *
     * @param array $actionConfig
     * @return TestController
     */
    public function getController(array $actionConfig = [])
    {
        $controller = new TestController('test', $this->app);
        $controller->layout = false;
        $controller->actionConfig = $actionConfig;

        return $controller;
    }

    public function testYiiException()
    {
        $this->app->getErrorHandler()->exception = new InvalidConfigException('This message will not be shown to the user');

        $this->assertEquals('Name: Invalid Configuration
Code: 500
Message: An internal server error occurred.
Exception: yii\exceptions\InvalidConfigException', $this->getController()->runAction('error'));
    }

    public function testUserException()
    {
        $this->app->getErrorHandler()->exception = new UserException('User can see this error message');

        $this->assertEquals('Name: Exception
Code: 500
Message: User can see this error message
Exception: yii\exceptions\UserException', $this->getController()->runAction('error'));
    }

    public function testAjaxRequest()
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $this->assertEquals('Not Found (#404): Page not found.', $this->getController()->runAction('error'));
    }

    public function testGenericException()
    {
        $this->app->getErrorHandler()->exception = new \InvalidArgumentException('This message will not be shown to the user');

        $this->assertEquals('Name: Error
Code: 500
Message: An internal server error occurred.
Exception: InvalidArgumentException', $this->getController()->runAction('error'));
    }

    public function testGenericExceptionCustomNameAndMessage()
    {
        $this->app->getErrorHandler()->exception = new \InvalidArgumentException('This message will not be shown to the user');

        $controller = $this->getController([
            'defaultName' => 'Oops...',
            'defaultMessage' => 'The system is drunk',
        ]);

        $this->assertEquals('Name: Oops...
Code: 500
Message: The system is drunk
Exception: InvalidArgumentException', $controller->runAction('error'));
    }

    public function testNoExceptionInHandler()
    {
        $this->assertEquals('Name: Not Found (#404)
Code: 404
Message: Page not found.
Exception: yii\web\NotFoundHttpException', $this->getController()->runAction('error'));
    }

    public function testDefaultView()
    {
        /** @var ErrorAction $action */
        $action = $this->getController()->createAction('error');

        // Unset view name. Class should try to load view that matches action name by default
        $action->view = null;
        $ds = preg_quote(DIRECTORY_SEPARATOR, '\\');
        $this->expectException(ViewNotFoundException::class);
        $this->expectExceptionMessageRegExp('#The view file does not exist: .*?views' . $ds . 'test' . $ds . 'error.php#');
        $this->invokeMethod($action, 'renderHtmlResponse');
    }

    public function testLayout()
    {
        $this->expectException(ViewNotFoundException::class);

        $this->getController([
            'layout' => 'non-existing',
        ])->runAction('error');

        $ds = preg_quote(DIRECTORY_SEPARATOR, '\\');
        $this->expectExceptionMessageRegExp('#The view file does not exist: .*?views' . $ds . 'layouts' . $ds . 'non-existing.php#');
    }
}

class TestController extends Controller
{
    private $actionConfig;

    public function setActionConfig($config = [])
    {
        $this->actionConfig = $config;
    }

    public function actions()
    {
        return [
            'error' => array_merge([
                '__class' => ErrorAction::class,
                'view' => '@yii/tests/data/views/error.php',
            ], $this->actionConfig),
        ];
    }
}
