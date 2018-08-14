<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

/**
 * Mock for the time() function for web classes.
 * @return int
 */
function time()
{
    return \yii\web\tests\UserTest::$time ?: \time();
}

namespace yii\web\tests;

use yii\helpers\Yii;
use yii\base\BaseObject;
use yii\rbac\CheckAccessInterface;
use yii\rbac\PhpManager;
use yii\http\Cookie;
use yii\http\CookieCollection;
use yii\web\ForbiddenHttpException;
use yii\tests\TestCase;

/**
 * @group web
 */
class UserTest extends TestCase
{
    /**
     * @var int virtual time to be returned by mocked time() function.
     * Null means normal time() behavior.
     */
    public static $time;

    protected function tearDown()
    {
        $this->app->session->removeAll();
        static::$time = null;
        parent::tearDown();
    }

    public function testLoginExpires()
    {
        if (getenv('TRAVIS') == 'true') {
            $this->markTestSkipped('Can not reliably test this on travis-ci.');
        }

        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::class,
                    'authTimeout' => 10,
                ],
                'authManager' => [
                    '__class' => PhpManager::class,
                    'itemFile' => '@runtime/user_test_rbac_items.php',
                     'assignmentFile' => '@runtime/user_test_rbac_assignments.php',
                     'ruleFile' => '@runtime/user_test_rbac_rules.php',
                ],
            ],
        ];
        $this->mockWebApplication($appConfig);

        $am = $this->app->authManager;
        $am->removeAll();
        $am->add($role = $am->createPermission('rUser'));
        $am->add($perm = $am->createPermission('doSomething'));
        $am->addChild($role, $perm);
        $am->assign($role, 'user1');

        $this->app->session->removeAll();
        static::$time = \time();
        $this->app->user->login(UserIdentity::findIdentity('user1'));

//        print_r($this->app->session);
//        print_r($_SESSION);

        $this->mockWebApplication($appConfig);
        $this->assertFalse($this->app->user->isGuest);
        $this->assertTrue($this->app->user->can('doSomething'));

        static::$time += 5;
        $this->mockWebApplication($appConfig);
        $this->assertFalse($this->app->user->isGuest);
        $this->assertTrue($this->app->user->can('doSomething'));

        static::$time += 11;
        $this->mockWebApplication($appConfig);
        $this->assertTrue($this->app->user->isGuest);
        $this->assertFalse($this->app->user->can('doSomething'));
    }

    /**
     * Make sure autologin works more than once.
     * @see https://github.com/yiisoft/yii2/issues/11825
     */
    public function testIssue11825()
    {
        global $cookiesMock;
        $cookiesMock = new CookieCollection();

        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::class,
                    'authTimeout' => 10,
                    'enableAutoLogin' => true,
                    'autoRenewCookie' => false,
                ],
                'response' => [
                    '__class' => MockResponse::class,
                ],
                'request' => [
                    '__class' => MockRequest::class,
                ],
            ],
        ];
        $this->mockWebApplication($appConfig);

        $this->app->session->removeAll();
        static::$time = \time();
        $this->app->user->login(UserIdentity::findIdentity('user1'), 20);

        // User is logged in
        $this->mockWebApplication($appConfig);
        $this->assertFalse($this->app->user->isGuest);

        // IdentityCookie is valid
        $this->app->session->removeAll();
        static::$time += 5;
        $this->mockWebApplication($appConfig);
        $this->assertFalse($this->app->user->isGuest);

        // IdentityCookie is still valid
        $this->app->session->removeAll();
        static::$time += 10;
        $this->mockWebApplication($appConfig);
        $this->assertFalse($this->app->user->isGuest);

        // IdentityCookie is no longer valid (we remove it manually, but browser will do it automatically)
        $this->invokeMethod($this->app->user, 'removeIdentityCookie');
        $this->app->session->removeAll();
        static::$time += 25;
        $this->mockWebApplication($appConfig);
        $this->assertTrue($this->app->user->isGuest);
    }

    public function testCookieCleanup()
    {
        global $cookiesMock;

        $cookiesMock = new CookieCollection();

        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::class,
                    'enableAutoLogin' => true,
                ],
                'response' => [
                    '__class' => MockResponse::class,
                ],
                'request' => [
                    '__class' => MockRequest::class,
                ],
            ],
        ];

        $this->mockWebApplication($appConfig);
        $this->app->session->removeAll();

        $cookie = new Cookie($this->app->user->identityCookie);
        $cookie->value = 'junk';
        $cookiesMock->add($cookie);
        $this->app->user->getIdentity();
        $this->assertEquals(strlen($cookiesMock->getValue($this->app->user->identityCookie['name'])), 0);

        $this->app->user->login(UserIdentity::findIdentity('user1'), 3600);
        $this->assertFalse($this->app->user->isGuest);
        $this->assertSame($this->app->user->id, 'user1');
        $this->assertNotEquals(strlen($cookiesMock->getValue($this->app->user->identityCookie['name'])), 0);

        $this->app->user->login(UserIdentity::findIdentity('user2'), 0);
        $this->assertFalse($this->app->user->isGuest);
        $this->assertSame($this->app->user->id, 'user2');
        $this->assertEquals(strlen($cookiesMock->getValue($this->app->user->identityCookie['name'])), 0);
    }

    /**
     * Resets request, response and $_SERVER.
     */
    protected function reset()
    {
        static $server;

        if (!isset($server)) {
            $server = $_SERVER;
        }

        $_SERVER = $server;
        $this->app->set('response', ['__class' => \yii\web\Response::class]);
        $this->app->set('request', [
            '__class' => \yii\web\Request::class,
            'scriptFile' => __DIR__ . '/index.php',
            'scriptUrl' => '/index.php',
            'url' => '',
        ]);
        $this->app->user->setReturnUrl(null);
    }

    public function testLoginRequired()
    {
        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::class,
                ],
                'authManager' => [
                    '__class' => PhpManager::class,
                    'itemFile' => '@runtime/user_test_rbac_items.php',
                    'assignmentFile' => '@runtime/user_test_rbac_assignments.php',
                    'ruleFile' => '@runtime/user_test_rbac_rules.php',
                ],
            ],
        ];
        $this->mockWebApplication($appConfig);


        $user = $this->app->user;

        $this->reset();
        $this->app->request->setUrl('normal');
        $user->loginRequired();
        $this->assertEquals('normal', $user->getReturnUrl());
        $this->assertTrue($this->app->response->getIsRedirection());

        $this->reset();
        $this->app->request->setUrl('ajax');
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $user->loginRequired();
        $this->assertEquals($this->app->getHomeUrl(), $user->getReturnUrl());
        // AJAX requests don't update returnUrl but they do cause redirection.
        $this->assertTrue($this->app->response->getIsRedirection());

        $user->loginRequired(false);
        $this->assertEquals('ajax', $user->getReturnUrl());
        $this->assertTrue($this->app->response->getIsRedirection());

        $this->reset();
        $this->app->request->setUrl('json-only');
        $_SERVER['HTTP_ACCEPT'] = 'Accept:  text/json, q=0.1';
        $user->loginRequired(true, false);
        $this->assertEquals('json-only', $user->getReturnUrl());
        $this->assertTrue($this->app->response->getIsRedirection());

        $this->reset();
        $this->app->request->setUrl('json-only');
        $_SERVER['HTTP_ACCEPT'] = 'text/json,q=0.1';
        $user->loginRequired(true, false);
        $this->assertEquals('json-only', $user->getReturnUrl());
        $this->assertTrue($this->app->response->getIsRedirection());

        $this->reset();
        $this->app->request->setUrl('accept-all');
        $_SERVER['HTTP_ACCEPT'] = '*/*;q=0.1';
        $user->loginRequired();
        $this->assertEquals('accept-all', $user->getReturnUrl());
        $this->assertTrue($this->app->response->getIsRedirection());

        $this->reset();
        $this->app->request->setUrl('json-and-accept-all');
        $_SERVER['HTTP_ACCEPT'] = 'text/json, */*; q=0.1';
        try {
            $user->loginRequired();
        } catch (ForbiddenHttpException $e) {
        }
        $this->assertFalse($this->app->response->getIsRedirection());

        $this->reset();
        $this->app->request->setUrl('accept-html-json');
        $_SERVER['HTTP_ACCEPT'] = 'text/json; q=1, text/html; q=0.1';
        $user->loginRequired();
        $this->assertEquals('accept-html-json', $user->getReturnUrl());
        $this->assertTrue($this->app->response->getIsRedirection());

        $this->reset();
        $this->app->request->setUrl('accept-html-json');
        $_SERVER['HTTP_ACCEPT'] = 'text/json;q=1,application/xhtml+xml;q=0.1';
        $user->loginRequired();
        $this->assertEquals('accept-html-json', $user->getReturnUrl());
        $this->assertTrue($this->app->response->getIsRedirection());

        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->app->request->setUrl('dont-set-return-url-on-post-request');
        $this->app->getSession()->set($user->returnUrlParam, null);
        $user->loginRequired();
        $this->assertNull($this->app->getSession()->get($user->returnUrlParam));

        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->app->request->setUrl('set-return-url-on-get-request');
        $this->app->getSession()->set($user->returnUrlParam, null);
        $user->loginRequired();
        $this->assertEquals('set-return-url-on-get-request', $this->app->getSession()->get($user->returnUrlParam));

        // Confirm that returnUrl is not set.
        $this->reset();
        $this->app->request->setUrl('json-only');
        $_SERVER['HTTP_ACCEPT'] = 'text/json;q=0.1';
        try {
            $user->loginRequired();
        } catch (ForbiddenHttpException $e) {
        }
        $this->assertNotEquals('json-only', $user->getReturnUrl());

        $this->reset();
        $_SERVER['HTTP_ACCEPT'] = 'text/json;q=0.1';
        $this->expectException('yii\\web\\ForbiddenHttpException');
        $user->loginRequired();
    }

    public function testLoginRequiredException1()
    {
        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::class,
                ],
                'authManager' => [
                    '__class' => PhpManager::class,
                    'itemFile' => '@runtime/user_test_rbac_items.php',
                    'assignmentFile' => '@runtime/user_test_rbac_assignments.php',
                    'ruleFile' => '@runtime/user_test_rbac_rules.php',
                ],
            ],
        ];

        $this->mockWebApplication($appConfig);
        $this->reset();
        $_SERVER['HTTP_ACCEPT'] = 'text/json,q=0.1';
        $this->expectException('yii\\web\\ForbiddenHttpException');
        $this->app->user->loginRequired();
    }

    public function testAccessChecker()
    {
        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::class,
                    'accessChecker' => AccessChecker::class
                ]
            ],
        ];

        $this->mockWebApplication($appConfig);
        $this->assertInstanceOf(AccessChecker::class, $this->app->user->accessChecker);
    }

    public function testGetIdentityException()
    {
        $session = $this->getMockBuilder(\yii\web\Session::class)
            ->setMethods(['getHasSessionId', 'get'])
            ->getMock();
        $session->expects($this->any())->method('getHasSessionId')->willReturn(true);
        $session->expects($this->any())->method('get')->with($this->equalTo('__id'))->willReturn('1');

        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => ExceptionIdentity::class,
                ],
                'session' => $session,
            ],
        ];
        $this->mockWebApplication($appConfig);

        $exceptionThrown = false;
        try {
            $this->app->getUser()->getIdentity();
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);

        // Do it again to make sure the exception is thrown the second time
        $this->expectException('Exception');
        $this->app->getUser()->getIdentity();
    }

}

static $cookiesMock;

class MockRequest extends \yii\web\Request
{
    public function getCookies()
    {
        global $cookiesMock;

        return $cookiesMock;
    }
}

class MockResponse extends \yii\web\Response
{
    public function getCookies()
    {
        global $cookiesMock;

        return $cookiesMock;
    }
}

class AccessChecker extends BaseObject implements CheckAccessInterface
{

    public function checkAccess($userId, $permissionName, $params = [])
    {
        // Implement checkAccess() method.
    }
}

class ExceptionIdentity extends \yii\web\tests\filters\stubs\UserIdentity
{
    public static function findIdentity($id)
    {
        throw new \Exception();
    }
}
