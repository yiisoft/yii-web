<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Web\Tests\Filters;

use yii\helpers\Yii;
use yii\base\Action;
use Yiisoft\Web\Filters\AjaxFilter;
use Yiisoft\Web\Controller;
use Yiisoft\Web\Request;
use yii\tests\TestCase;

/**
 * @group filters
 */
class AjaxFilterTest extends TestCase
{
    /**
     * @param bool $isAjax
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
        $this->expectException('Yiisoft\Web\BadRequestHttpException');
        $filter->beforeAction($action);
    }
}
