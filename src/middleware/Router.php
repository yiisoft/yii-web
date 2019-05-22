<?php


namespace yii\web\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use yii\web\router\UrlMatcherInterface;

class Router implements MiddlewareInterface
{
    private $matcher;

    public function __construct(UrlMatcherInterface $matcher)
    {
        $this->matcher = $matcher;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->matcher->match($request);

        if ($result === null) {
            return $handler->handle($request);
        }

        if ($result->isSuccess()) {
            foreach ($result->parameters() as $parameter => $value) {
                $request = $request->withAttribute($parameter, $value);
            }
        }

        return $result->process($request, $handler);
    }
}
