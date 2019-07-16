<?php
namespace Yiisoft\Yii\Web;

use Yiisoft\Router\Method;
use Yiisoft\Yii\Web\Emitter\EmitterInterface;
use Yiisoft\Yii\Web\ErrorHandler\ErrorHandler;

/**
 * Application is the entry point for a web application.
 *
 * For more details and usage information on Application, see the [guide article on applications](guide:structure-applications).
 */
final class Application
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
     * @param ServerRequestFactory $requestFactory
     * @param MiddlewareDispatcher $dispatcher
     * @param EmitterInterface $emitter
     */
    public function __construct(ServerRequestFactory $requestFactory, MiddlewareDispatcher $dispatcher, EmitterInterface $emitter, ErrorHandler $errorHandler)
    {
        $this->requestFactory = $requestFactory;
        $this->dispatcher = $dispatcher;
        $this->emitter = $emitter;

        $errorHandler->register();
    }

    public function run(): bool
    {
        $request = $this->requestFactory->createFromGlobals();
        $response = $this->dispatcher->handle($request);

        $emitter = $this->emitter;
        if ($request->getMethod() === Method::HEAD) {
            $emitter = $emitter->withoutBody();
        }

        return $emitter->emit($response);
    }
}
