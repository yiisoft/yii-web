<?php


namespace yii\middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Router implements MiddlewareInterface
{
    private $responseFactory;
    private $routes;

    public function __construct(array $routes, ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->routes = $routes;
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // $result = $this->router->match($request);
        // if ($result->isSuccess()) {
        //            return $result->getHandler()->handle($request);
        //        }
        // return $handler->handle($request);

        $path = $request->getUri()->getPath();

        if ($path === '/test') {
            // obtain $handler, process


            $response = $this->responseFactory->createResponse(200);
            $response->getBody()->write($path);

            return $response;
        }

        return $handler->handle($request);
    }
}
