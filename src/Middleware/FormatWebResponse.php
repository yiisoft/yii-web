<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Formatter\ResponseFormatterInterface;
use Yiisoft\Yii\Web\WebResponse;

class FormatWebResponse implements MiddlewareInterface
{
    private ResponseFormatterInterface $responseFormatter;

    public function __construct(ResponseFormatterInterface $responseFormatter)
    {
        $this->responseFormatter = $responseFormatter;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if ($response instanceof WebResponse && !$response->hasResponseFormatter()) {
            $response = $response->withResponseFormatter($this->responseFormatter);
        }

        return $response;
    }
}
