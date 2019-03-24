<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use yii\web\emitter\EmitterInterface;

/**
 * Application is the entry point for a web application.
 *
 * For more details and usage information on Application, see the [guide article on applications](guide:structure-applications).
 */
class Application
{
    /**
     * @var ServerRequestFactory
     */
    private $requestFactory;

    /**
     * @var MiddlewareDispatcher
     */
    private $dispatcher;

    /**
     * @var EmitterInterface
     */
    private $emitter;

    /**
     * Application constructor.
     *
     * @param ServerRequestFactory $requestFactory
     * @param MiddlewareDispatcher $dispatcher
     * @param EmitterInterface     $emitter
     */
    public function __construct(ServerRequestFactory $requestFactory, MiddlewareDispatcher $dispatcher, EmitterInterface $emitter)
    {
        $this->requestFactory = $requestFactory;
        $this->dispatcher = $dispatcher;
        $this->emitter = $emitter;
    }

    public function run(): bool
    {
        $request = $this->requestFactory->createFromGlobals();
        $response = $this->dispatcher->handle($request);

        return $this->emitter->emit($response);
    }
}
