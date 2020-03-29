<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Formatter\XmlResponseFormatter;
use Yiisoft\Yii\Web\Response;

class DeferredXmlResponseFormatter implements MiddlewareInterface
{
    private XmlResponseFormatter $responseFormatter;

    public function __construct(XmlResponseFormatter $responseFormatter)
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
