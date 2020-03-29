<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Formatter\JsonResponseFormatter;
use Yiisoft\Yii\Web\Response;

class DeferredJsonResponseFormatter implements MiddlewareInterface
{
    private JsonResponseFormatter $responseFormatter;

    public function __construct(JsonResponseFormatter $responseFormatter)
    {
        $this->responseFormatter = $responseFormatter;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if ($response instanceof Response && !$response->hasResponseFormatter()) {
            $response = $this->responseFormatter->format($response);
        }

        return $response;
    }
}
