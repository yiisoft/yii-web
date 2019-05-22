<?php


namespace yii\web\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use yii\web\router\MatchingResult;

class RoutingDispatcher implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /* @var MatchingResult $routeResult */
        $routeResult = $request->getAttribute(MatchingResult::class);

        if ($routeResult === null) {
            return $handler->handle($request);
        }
        return $routeResult->process($request, $handler);
    }
}
