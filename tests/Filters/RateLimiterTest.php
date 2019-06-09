<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Web\Tests\Filters;

use Prophecy\Argument;
use yii\helpers\Yii;
use Yiisoft\Web\Filters\RateLimiter;
use Yiisoft\Log\Logger;
use Yiisoft\Web\Request;
use Yiisoft\Web\Response;
use Yiisoft\Web\User;
use Yiisoft\Web\Tests\Filters\Stubs\RateLimit;
use Yiisoft\Web\Tests\Filters\Stubs\UserIdentity;
use yii\tests\TestCase;

/**
 *  @group filters
 */
class RateLimiterTest extends TestCase
{
    private $_originalLogger;

    protected function setUp()
    {
        parent::setUp();

        $this->_originalLogger = $this->container->getDefinition('logger');
        /* @var $logger Logger|\Prophecy\ObjectProphecy */
        $logger = $this->prophesize(Logger::class);
        $logger
            ->log(Argument::any(), Argument::any(), Argument::any())
            ->will(function ($parameters, $logger) {
                $logger->messages = $parameters;
            });

        $this->container->set('logger', $logger->reveal());

        $this->mockWebApplication();
    }
    protected function tearDown()
    {
        parent::tearDown();
        $this->container->set('logger', $this->_originalLogger);
    }

    public function testInitFilledRequest()
    {
        $request = new Request($this->app);
        $rateLimiter = new RateLimiter($request, $this->app->response);

        $this->assertSame($request, $rateLimiter->request);
    }

    public function testInitNotFilledRequest()
    {
        $rateLimiter = $this->factory->create(RateLimiter::class);

        $this->assertInstanceOf(Request::class, $rateLimiter->request);
    }

    public function testInitFilledResponse()
    {
        $response = new Response($this->app);
        $rateLimiter = new RateLimiter($this->app->request, $response);

        $this->assertSame($response, $rateLimiter->response);
    }

    public function testInitNotFilledResponse()
    {
        $rateLimiter = $this->factory->create(RateLimiter::class);

        $this->assertInstanceOf(Response::class, $rateLimiter->response);
    }

    public function testBeforeActionUserInstanceOfRateLimitInterface()
    {
        $rateLimiter = $this->factory->create(RateLimiter::class);
        $rateLimit = new RateLimit();
        $rateLimit->setAllowance([1, time()])
            ->setRateLimit([1, 1]);
        $rateLimiter->user = $rateLimit;

        $result = $rateLimiter->beforeAction('test');

        $this->assertContains('Check rate limit', $this->container->get('logger')->messages);
        $this->assertTrue($result);
    }

    public function testBeforeActionUserNotInstanceOfRateLimitInterface()
    {
        $rateLimiter = $this->factory->create(RateLimiter::class);
        $rateLimiter->user = 'User';

        $result = $rateLimiter->beforeAction('test');

        $this->assertContains('Rate limit skipped: "user" does not implement RateLimitInterface.', $this->container->get('logger')->messages);
        $this->assertTrue($result);
    }

    public function testBeforeActionEmptyUser()
    {
        $user = $this->factory->create([
            '__class' => User::class,
            'identityClass' => RateLimit::class,
        ]);
        $this->container->set('user', $user);
        $rateLimiter = $this->factory->create(RateLimiter::class);

        $result = $rateLimiter->beforeAction('test');

        $this->assertContains('Rate limit skipped: user not logged in.', $this->container->get('logger')->messages);
        $this->assertTrue($result);
    }

    public function testCheckRateLimitTooManyRequests()
    {
        /* @var $rateLimit UserIdentity|\Prophecy\ObjectProphecy */
        $rateLimit = new RateLimit();
        $rateLimit
            ->setRateLimit([1, 1])
            ->setAllowance([1, time() + 2]);
        $rateLimiter = $this->factory->create(RateLimiter::class);

        $this->expectException(\Yiisoft\Web\TooManyRequestsHttpException::class);
        $rateLimiter->checkRateLimit($rateLimit, $this->app->request, $this->app->response, 'testAction');
    }

    public function testCheckRateaddRateLimitHeaders()
    {
        /* @var $user UserIdentity|\Prophecy\ObjectProphecy */
        $rateLimit = new RateLimit();
        $rateLimit
            ->setRateLimit([2, 10])
            ->setAllowance([2, time()]);

        $rateLimiter = $this->factory->create(RateLimiter::class);
        $response = $this->app->response;
        $rateLimiter->checkRateLimit($rateLimit, $this->app->request, $response, 'testAction');
        $headers = $response->getHeaderCollection();
        $this->assertEquals(2, $headers->get('X-Rate-Limit-Limit'));
        $this->assertEquals(1, $headers->get('X-Rate-Limit-Remaining'));
        $this->assertEquals(5, $headers->get('X-Rate-Limit-Reset'));
    }

    public function testAddRateLimitHeadersDisabledRateLimitHeaders()
    {
        $rateLimiter = $this->factory->create(RateLimiter::class);
        $rateLimiter->enableRateLimitHeaders = false;
        $response = $this->app->response;

        $rateLimiter->addRateLimitHeaders($response, 1, 0, 0);
        $this->assertCount(0, $response->getHeaders());
    }

    public function testAddRateLimitHeadersEnabledRateLimitHeaders()
    {
        $rateLimiter = $this->factory->create(RateLimiter::class);
        $rateLimiter->enableRateLimitHeaders = true;
        $response = $this->app->response;

        $rateLimiter->addRateLimitHeaders($response, 1, 0, 0);
        $this->assertCount(3, $response->getHeaders());
    }
}
