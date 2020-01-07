<?php
namespace Yiisoft\Yii\Web;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Yii\Web\Emitter\EmitterInterface;
use Yiisoft\Yii\Web\ErrorHandler\ErrorHandler;

/**
 * Application is the entry point for a web application.
 *
 * For more details and usage information on Application, see the [guide article on applications](guide:structure-applications).
 */
final class Application
{
    private MiddlewareDispatcher $dispatcher;
    private EmitterInterface $emitter;

    public function __construct(MiddlewareDispatcher $dispatcher, EmitterInterface $emitter, ErrorHandler $errorHandler)
    {
        $this->dispatcher = $dispatcher;
        $this->emitter = $emitter;

        $errorHandler->register();
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): bool
    {
        $response = $this->dispatcher->dispatch($request);
        return $this->emitter->emit($response, $request->getMethod() === Method::HEAD);
    }
}
