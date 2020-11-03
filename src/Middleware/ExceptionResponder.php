<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Injector\Injector;

class ExceptionResponder implements MiddlewareInterface
{
    private array $exceptionMap;
    private ResponseFactoryInterface $responseFactory;
    private Injector $injector;

    /**
     * @param array $exceptionMap
     * @psalm-param array{string, int|callable}
     */
    public function __construct(array $exceptionMap, ResponseFactoryInterface $responseFactory, Injector $injector)
    {
        $this->exceptionMap = $exceptionMap;
        $this->responseFactory = $responseFactory;
        $this->injector = $injector;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $t) {
            foreach ($this->exceptionMap as $exceptionType => $responseHandler) {
                if ($t instanceof $exceptionType) {
                    if (is_int($responseHandler)) {
                        return $this->responseFactory->createResponse($responseHandler);
                    }

                    if (is_callable($responseHandler)) {
                        return $this->injector->invoke($responseHandler);
                    }
                }
            }
            throw $t;
        }
    }
}
