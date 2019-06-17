<?php
namespace Yiisoft\Web\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Tags request with a random value that could be later used for identifying it.
 */
class TagRequest implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $handler->handle($request->withAttribute('requestTag', $this->getRequestTag()));
    }

    protected function getRequestTag(): string
    {
        return dechex(microtime(true) * 1000000);
    }
}
