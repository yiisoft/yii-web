<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Web;

use Yiisoft\Web\Emitter\EmitterInterface;

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
    private $_requestFactory;

    /**
     * @var MiddlewareDispatcher
     */
    private $_dispatcher;

    /**
     * @var EmitterInterface
     */
    private $_emitter;

    /**
     * Application constructor.
     * @param ServerRequestFactory $requestFactory
     * @param MiddlewareDispatcher $dispatcher
     * @param EmitterInterface $emitter
     */
    public function __construct(ServerRequestFactory $requestFactory, MiddlewareDispatcher $dispatcher, EmitterInterface $emitter)
    {
        $this->_requestFactory = $requestFactory;
        $this->_dispatcher = $dispatcher;
        $this->_emitter = $emitter;
    }

    public function run(): bool
    {
        $request = $this->_requestFactory->createFromGlobals();
        $response = $this->_dispatcher->handle($request);
        return $this->_emitter->emit($response);
    }
}
