<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests\filters;

use yii\base\Action;
use yii\helpers\Yii;
use yii\tests\TestCase;
use yii\web\Controller;
use yii\web\filters\AjaxFilter;
use yii\web\Request;

/**
 * @group filters
 */
class AjaxFilterTest extends TestCase
{
    /**
     * @param bool $isAjax
     *
     * @return Request
     */
    protected function mockRequest($isAjax)
    {
        /** @var Request $request */
        $request = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getIsAjax'])
            ->getMock();
        $request->method('getIsAjax')->willReturn($isAjax);

        return $request;
    }

    public function testFilter()
    {
        $this->mockWebApplication();
        $controller = new Controller('id', $this->app);
        $action = new Action('test', $controller);
        $filter = new AjaxFilter();

        $filter->request = $this->mockRequest(true);
        $this->assertTrue($filter->beforeAction($action));

        $filter->request = $this->mockRequest(false);
        $this->expectException('yii\web\BadRequestHttpException');
        $filter->beforeAction($action);
    }
}
