<?php


namespace Yiisoft\Yii\Web\Middleware;


use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExceptionResponder implements MiddlewareInterface
{
    private array $exceptionMap;
    private ResponseFactoryInterface $responseFactory;

    /**
     * @param array $exceptionMap
     * @psalm-param array{string, int}
     */
    public function __construct(array $exceptionMap, ResponseFactoryInterface $responseFactory)
    {
        $this->exceptionMap = $exceptionMap;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $t) {
            foreach ($this->exceptionMap as $exceptionType => $responseCode) {
                if ($t instanceof $exceptionType) {
                    return $this->responseFactory->createResponse($responseCode);
                }
            }
        }
    }
}
