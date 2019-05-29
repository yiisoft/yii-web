<?php
namespace yii\web\tests\filters;

use Closure;
use yii\base\Action;
use yii\web\tests\filters\stubs\DenyAll;
use Yiisoft\Access\CheckAccessInterface;
use yii\web\filters\AccessRule;
use yii\web\Controller;
use yii\web\Request;
use yii\web\User;
use yii\web\tests\filters\stubs\AccessChecker;
use yii\web\tests\filters\stubs\UserIdentity;

/**
 * @group filters
 */
class AccessRuleTest extends \yii\tests\TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $_SERVER['SCRIPT_FILENAME'] = '/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->mockWebApplication();
    }

    /**
     * @param string $method
     * @return Request
     */
    protected function mockRequest($method = 'GET')
    {
        /** @var Request $request */
        $request = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getMethod'])
            ->getMock();
        $request->method('getMethod')->willReturn($method);

        return $request;
    }

    protected function mockUser(CheckAccessInterface $accessChecker, $userid = null)
    {
        $user = new User($this->app, $accessChecker);
        $user->identityClass = UserIdentity::class;
        $user->enableAutoLogin = false;

        if ($userid !== null) {
            $user->setIdentity(UserIdentity::findIdentity($userid));
        }

        return $user;
    }

    /**
     * @return Action
     */
    protected function mockAction()
    {
        $controller = new Controller('site', $this->app);
        return new Action('test', $controller);
    }

    protected function mockAccessChecker(): CheckAccessInterface
    {
        $auth = new AccessChecker();

        // admin
        $auth->addPermissions('user1', [
            'createPost',
            'updatePost'
        ]);

        // author
        $auth->addPermissions('user2', [
            'createPost',
            'updatePost' => function ($userId, array $parameters) {
                return $parameters['authorID'] === $userId;
            },
        ]);

        return $auth;
    }

    public function testMatchAction()
    {
        $action = $this->mockAction();
        $user = false;
        $request = $this->mockRequest();

        $rule = new AccessRule([
            'allow' => true,
            'actions' => ['test', 'other'],
        ]);

        $action->id = 'test';
        $this->assertTrue($rule->allows($action, $user, $request));
        $action->id = 'other';
        $this->assertTrue($rule->allows($action, $user, $request));
        $action->id = 'foreign';
        $this->assertNull($rule->allows($action, $user, $request));

        $rule->allow = false;

        $action->id = 'test';
        $this->assertFalse($rule->allows($action, $user, $request));
        $action->id = 'other';
        $this->assertFalse($rule->allows($action, $user, $request));
        $action->id = 'foreign';
        $this->assertNull($rule->allows($action, $user, $request));
    }

    public function testMatchController()
    {
        $action = $this->mockAction();
        $user = false;
        $request = $this->mockRequest();

        $rule = new AccessRule([
            'allow' => true,
            'controllers' => ['test', 'other'],
        ]);

        $action->controller->id = 'test';
        $this->assertTrue($rule->allows($action, $user, $request));

        $action->controller->id = 'other';
        $this->assertTrue($rule->allows($action, $user, $request));
        $action->controller->id = 'foreign';
        $this->assertNull($rule->allows($action, $user, $request));

        $rule->allow = false;

        $action->controller->id = 'test';
        $this->assertFalse($rule->allows($action, $user, $request));
        $action->controller->id = 'other';
        $this->assertFalse($rule->allows($action, $user, $request));
        $action->controller->id = 'foreign';
        $this->assertNull($rule->allows($action, $user, $request));
    }

    /**
     * @depends testMatchController
     */
    public function testMatchControllerWildcard()
    {
        $action = $this->mockAction();
        $user = false;
        $request = $this->mockRequest();

        $rule = new AccessRule([
            'allow' => true,
            'controllers' => ['module/*', '*/controller'],
        ]);

        $action->controller->id = 'module/test';
        $this->assertTrue($rule->allows($action, $user, $request));
        $action->controller->id = 'any-module/controller';
        $this->assertTrue($rule->allows($action, $user, $request));
        $action->controller->id = 'other/other';
        $this->assertNull($rule->allows($action, $user, $request));

        $rule->allow = false;

        $action->controller->id = 'module/test';
        $this->assertFalse($rule->allows($action, $user, $request));
        $action->controller->id = 'any-module/controller';
        $this->assertFalse($rule->allows($action, $user, $request));
        $action->controller->id = 'other/other';
        $this->assertNull($rule->allows($action, $user, $request));
    }

    /**
     * Data provider for testMatchPermission.
     *
     * @return array or arrays
     *           the id of the action
     *           should the action allow (true) or disallow (false)
     *           test user id
     *           expected match result (true, false, null)
     */
    public function matchPermissionProvider()
    {
        return [
            ['create', true,  'user1',   [], true],
            ['create', true,  'user2',   [], true],
            ['create', true,  'user3',   [], null],
            ['create', true,  'unknown', [], null],
            ['create', false, 'user1',   [], false],
            ['create', false, 'user2',   [], false],
            ['create', false, 'user3',   [], null],
            ['create', false, 'unknown', [], null],

            // user2 is author, can only edit own posts
            ['update', true,  'user2',   ['authorID' => 'user2'], true],
            ['update', true,  'user2',   ['authorID' => 'user1'], null],
            // user1 is admin, can update all posts
            ['update', true,  'user1',   ['authorID' => 'user1'], true],
            ['update', true,  'user1',   ['authorID' => 'user2'], true],
            // unknown user can not edit anything
            ['update', true,  'unknown', ['authorID' => 'user1'], null],
            ['update', true,  'unknown', ['authorID' => 'user2'], null],

            // user2 is author, can only edit own posts
            ['update', true,  'user2',   function () {
                return ['authorID' => 'user2'];
            }, true],
            ['update', true,  'user2',   function () {
                return ['authorID' => 'user1'];
            }, null],
            // user1 is admin, can update all posts
            ['update', true,  'user1',   function () {
                return ['authorID' => 'user1'];
            }, true],
            ['update', true,  'user1',   function () {
                return ['authorID' => 'user2'];
            }, true],
            // unknown user can not edit anything
            ['update', true,  'unknown', function () {
                return ['authorID' => 'user1'];
            }, null],
            ['update', true,  'unknown', function () {
                return ['authorID' => 'user2'];
            }, null],
        ];
    }

    /**
     * Test that a user matches certain roles.
     *
     * @dataProvider matchPermissionProvider
     * @param string $actionId the action id
     * @param bool $allow whether the rule should allow access
     * @param string $userid the userid to check
     * @param array|Closure $permissionParameters params for $roleParams
     * @param bool $expected the expected result or null
     */
    public function testMatchPermission($actionId, $allow, $userid, $permissionParameters, $expected)
    {
        $action = $this->mockAction();
        $accessChecker = $this->mockAccessChecker();
        $request = $this->mockRequest();

        $rule = new AccessRule([
            'allow' => $allow,
            'permissions' => [$actionId === 'create' ? 'createPost' : 'updatePost'],
            'actions' => [$actionId],
            'permissionParameters' => $permissionParameters,
        ]);

        $action->id = $actionId;

        $user = $this->mockUser($accessChecker, $userid);
        $this->assertEquals($expected, $rule->allows($action, $user, $request));
    }

    /**
     * Test that matching role is not possible without User component.
     *
     * @see https://github.com/yiisoft/yii2/issues/4793
     */
    public function testMatchRoleWithoutUser()
    {
        $action = $this->mockAction();
        $request = $this->mockRequest();

        $rule = new AccessRule([
            'allow' => true,
            'permissions' => ['@'],
        ]);

        $this->expectException('yii\exceptions\InvalidConfigException');
        $rule->allows($action, false, $request);
    }

    public function testMatchRoleSpecial()
    {
        $action = $this->mockAction();
        $request = $this->mockRequest();
        $accessChecker = $this->mockAccessChecker();

        $authenticated = $this->mockUser($accessChecker, 'user1');
        $guest = $this->mockUser($accessChecker, 'unknown');

        $rule = new AccessRule();
        $rule->allow = true;
        $rule->permissionParameters = function () {
            $this->assertTrue(false, 'Should not be executed');
        };

        $rule->permissions = ['@'];
        $this->assertTrue($rule->allows($action, $authenticated, $request));
        $this->assertNull($rule->allows($action, $guest, $request));

        $rule->permissions = ['?'];
        $this->assertNull($rule->allows($action, $authenticated, $request));
        $this->assertTrue($rule->allows($action, $guest, $request));

        $rule->permissions = ['?', '@'];
        $this->assertTrue($rule->allows($action, $authenticated, $request));
        $this->assertTrue($rule->allows($action, $guest, $request));
    }

    public function testMatchRolesAndPermissions()
    {
        $action = $this->mockAction();
        $user = $this->getMockBuilder(User::class)
            ->setConstructorArgs([$this->app, new DenyAll()])
            ->getMock();
        $user->identityCLass = UserIdentity::class;

        $rule = new AccessRule([
            'allow' => true,
        ]);

        $request = $this->mockRequest('GET');
        $this->assertTrue($rule->allows($action, $user, $request));

        $rule->permissions = ['allowed_role_1', 'allowed_role_2'];
        $this->assertNull($rule->allows($action, $user, $request));

        $rule->permissions = ['allowed_permission_1', 'allowed_permission_2'];
        $this->assertNull($rule->allows($action, $user, $request));

        $rule->permissions = ['allowed_role_1', 'allowed_role_2', 'allowed_permission_1', 'allowed_permission_2'];
        $this->assertNull($rule->allows($action, $user, $request));

        $user->method('can')->willReturn(true);
        $rule->permissions = ['allowed_role_1', 'allowed_role_2'];
        $this->assertTrue($rule->allows($action, $user, $request));

        $rule->permissions = ['allowed_permission_1', 'allowed_permission_2'];
        $this->assertTrue($rule->allows($action, $user, $request));

        $rule->permissions = ['allowed_role_1', 'allowed_role_2', 'allowed_permission_1', 'allowed_permission_2'];
        $this->assertTrue($rule->allows($action, $user, $request));
    }

    public function testMatchVerb()
    {
        $action = $this->mockAction();
        $user = false;

        $rule = new AccessRule([
            'allow' => true,
            'verbs' => ['POST', 'get'],
        ]);

        $request = $this->mockRequest('GET');
        $this->assertTrue($rule->allows($action, $user, $request));

        $request = $this->mockRequest('POST');
        $this->assertTrue($rule->allows($action, $user, $request));

        $request = $this->mockRequest('HEAD');
        $this->assertNull($rule->allows($action, $user, $request));

        $request = $this->mockRequest('get');
        $this->assertTrue($rule->allows($action, $user, $request));

        $request = $this->mockRequest('post');
        $this->assertTrue($rule->allows($action, $user, $request));

        $request = $this->mockRequest('head');
        $this->assertNull($rule->allows($action, $user, $request));
    }

    // TODO test match custom callback

    public function testMatchIP()
    {
        $action = $this->mockAction();
        $user = false;
        $request = $this->mockRequest();

        $rule = new AccessRule();

        // by default match all IPs
        $rule->allow = true;
        $this->assertTrue($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertFalse($rule->allows($action, $user, $request));

        // empty IPs = match all IPs
        $rule->ips = [];
        $rule->allow = true;
        $this->assertTrue($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertFalse($rule->allows($action, $user, $request));

        // match, one IP
        $request->setServerParams([
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $rule->ips = ['127.0.0.1'];
        $rule->allow = true;
        $this->assertTrue($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertFalse($rule->allows($action, $user, $request));

        // no match, one IP
        $request->setServerParams([
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $rule->ips = ['192.168.0.1'];
        $rule->allow = true;
        $this->assertNull($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertNull($rule->allows($action, $user, $request));

        // no partial match, one IP
        $request->setServerParams([
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $rule->ips = ['127.0.0.10'];
        $rule->allow = true;
        $this->assertNull($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertNull($rule->allows($action, $user, $request));
        $request->setServerParams([
            'REMOTE_ADDR' => '127.0.0.10'
        ]);
        $rule->ips = ['127.0.0.1'];
        $rule->allow = true;
        $this->assertNull($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertNull($rule->allows($action, $user, $request));

        // match, one IP IPv6
        $request->setServerParams([
            'REMOTE_ADDR' => '::1'
        ]);
        $rule->ips = ['::1'];
        $rule->allow = true;
        $this->assertTrue($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertFalse($rule->allows($action, $user, $request));

        // no match, one IP IPv6
        $request->setServerParams([
            'REMOTE_ADDR' => '::1'
        ]);
        $rule->ips = ['dead::beaf::1'];
        $rule->allow = true;
        $this->assertNull($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertNull($rule->allows($action, $user, $request));

        // no partial match, one IP IPv6
        $request->setServerParams([
            'REMOTE_ADDR' => '::1'
        ]);
        $rule->ips = ['::123'];
        $rule->allow = true;
        $this->assertNull($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertNull($rule->allows($action, $user, $request));

        $request->setServerParams([
            'REMOTE_ADDR' => '::123'
        ]);
        $rule->ips = ['::1'];
        $rule->allow = true;
        $this->assertNull($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertNull($rule->allows($action, $user, $request));

        // undefined IP
        $request->setServerParams([
            'REMOTE_ADDR' => null
        ]);
        $rule->ips = ['192.168.*'];
        $rule->allow = true;
        $this->assertNull($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertNull($rule->allows($action, $user, $request));
    }

    public function testMatchIPWildcard()
    {
        $action = $this->mockAction();
        $user = false;
        $request = $this->mockRequest();

        $rule = new AccessRule();

        // no match
        $request->setServerParams([
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $rule->ips = ['192.168.*'];
        $rule->allow = true;
        $this->assertNull($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertNull($rule->allows($action, $user, $request));

        // match
        $request->setServerParams([
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $rule->ips = ['127.0.*'];
        $rule->allow = true;
        $this->assertTrue($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertFalse($rule->allows($action, $user, $request));

        // match, IPv6
        $request->setServerParams([
            'REMOTE_ADDR' => '2a01:4f8:120:7202::2'
        ]);
        $rule->ips = ['2a01:4f8:120:*'];
        $rule->allow = true;
        $this->assertTrue($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertFalse($rule->allows($action, $user, $request));

        // no match, IPv6
        $request->setServerParams([
            'REMOTE_ADDR' => '::1'
        ]);
        $rule->ips = ['2a01:4f8:120:*'];
        $rule->allow = true;
        $this->assertNull($rule->allows($action, $user, $request));
        $rule->allow = false;
        $this->assertNull($rule->allows($action, $user, $request));
    }
}
