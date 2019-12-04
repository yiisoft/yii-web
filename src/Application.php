<?php
namespace Yiisoft\Yii\Web;

use Psr\Http\Message\ServerRequestInterface;
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
     * @var MiddlewareDispatcher
     */
    private $dispatcher;

    /**
     * @var EmitterInterface
     */
    private $emitter;

    /**
     * Application constructor.
     * @param MiddlewareDispatcher $dispatcher
     * @param EmitterInterface $emitter
     */
    public function __construct(MiddlewareDispatcher $dispatcher, EmitterInterface $emitter, ErrorHandler $errorHandler)
    {
        $this->dispatcher = $dispatcher;
        $this->emitter = $emitter;

        $errorHandler->register();
    }

    public function handle(ServerRequestInterface $request): bool
    {
        $response = $this->dispatcher->dispatch($request);
        return $this->emitter->emit($response, $request->getMethod() === Method::HEAD);
    }
}
