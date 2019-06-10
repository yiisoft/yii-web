<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Web\Tests\Filters\Auth;

use yii\base\Action;
use Yiisoft\Web\Filters\Auth\AuthMethod;
use Yiisoft\Web\Controller;
use Yiisoft\Web\Tests\Filters\Stubs\UserIdentity;
use yii\tests\TestCase;
use Yiisoft\Web\User;

class AuthMethodTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->mockWebApplication();
        $this->container->setAll([
            'user' => [
                '__class' => User::class,
                'identityClass' => UserIdentity::class,
            ],
        ]);
    }

    /**
     * Creates mock for [[AuthMethod]] filter.
     * @param callable $authenticateCallback callback, which result should [[authenticate()]] method return.
     * @return AuthMethod filter instance.
     */
    protected function createFilter($authenticateCallback)
    {
        $filter = $this->getMockBuilder(AuthMethod::class)
            ->setMethods(['authenticate'])
            ->getMock();
        $filter->method('authenticate')->willReturnCallback($authenticateCallback);

        return $filter;
    }

    /**
     * Creates test action.
     * @param array $config action configuration.
     * @return Action action instance.
     */
    protected function createAction(array $config = [])
    {
        $controller = new Controller('test', $this->app);
        return $this->factory->create([
            '__class' => Action::class,
            '__construct()' => [
                'id' => 'index',
                'controller' => $controller
            ],
        ], $config);
    }

    // Tests :

    public function testBeforeAction()
    {
        $action = $this->createAction();

        $filter = $this->createFilter(function () {
            return new \stdClass();
        });
        $this->assertTrue($filter->beforeAction($action));

        $filter = $this->createFilter(function () {
            return null;
        });
        $this->expectException('Yiisoft\Web\UnauthorizedHttpException');
        $this->assertTrue($filter->beforeAction($action));
    }

    public function testIsOptional()
    {
        $reflection = new \ReflectionClass(AuthMethod::class);
        $method = $reflection->getMethod('isOptional');
        $method->setAccessible(true);

        $filter = $this->createFilter(function () {
            return new \stdClass();
        });

        $filter->optional = ['some'];
        $this->assertFalse($method->invokeArgs($filter, [$this->createAction(['id' => 'index'])]));
        $this->assertTrue($method->invokeArgs($filter, [$this->createAction(['id' => 'some'])]));

        $filter->optional = ['test/*'];
        $this->assertFalse($method->invokeArgs($filter, [$this->createAction(['id' => 'index'])]));
        $this->assertTrue($method->invokeArgs($filter, [$this->createAction(['id' => 'test/index'])]));
    }
}
