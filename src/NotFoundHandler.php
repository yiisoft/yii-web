<?php

namespace Yiisoft\Web;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NotFoundHandler implements RequestHandlerInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $_responseFactory;

    /**
     * NotFoundHandler constructor.
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->_responseFactory = $responseFactory;
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $response = $this->_responseFactory->createResponse(404);
        $response->getBody()->write("We were unable to find the page $path.");
        return $response;
    }
}
