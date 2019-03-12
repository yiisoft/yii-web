<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Psr\Container\ContainerInterface;
use yii\web\emitter\EmitterInterface;

/**
 * Application is the entry point for a web application.
 *
 * For more details and usage information on Application, see the [guide article on applications](guide:structure-applications).
 */
class Application
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Application constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function run(): bool
    {
        $container = $this->container;
        $request = $container->get(ServerRequestFactory::class)->createFromGlobals();
        $response = $container->get(MiddlewareDispatcher::class)->handle($request);
        return $container->get(EmitterInterface::class)->emit($response);
    }
}
