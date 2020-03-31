<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Formatter\ResponseFormatterInterface;
use Yiisoft\Yii\Web\WebResponse;

class WebResponseFormatter implements MiddlewareInterface
{
    private ResponseFormatterInterface $responseFormatter;

    private bool $forceRender;

    public function __construct(ResponseFormatterInterface $responseFormatter, bool $forceRender = false)
    {
        $this->responseFormatter = $responseFormatter;
        $this->forceRender = $forceRender;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if ($response instanceof WebResponse && !$response->hasResponseFormatter()) {
            if ($this->forceRender) {
                $response = $this->responseFormatter->format($response);
            } else {
                $response = $response->withResponseFormatter($this->responseFormatter);
            }
        }

        return $response;
    }
}
