<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Formatter\JsonResponseFormatter as ResponseFormatter;
use Yiisoft\Yii\Web\Response;

class JsonResponseFormatter implements MiddlewareInterface
{
    private ResponseFormatter $responseFormatter;

    public function __construct(ResponseFormatter $responseFormatter)
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
