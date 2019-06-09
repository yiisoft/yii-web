<?php

namespace Yiisoft\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IpFilter implements MiddlewareInterface
{
    private $_allowedIp;
    private $_responseFactory;

    public function __construct(string $allowedIp, ResponseFactoryInterface $responseFactory)
    {
        $this->_allowedIp = $allowedIp;
        $this->_responseFactory = $responseFactory;
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
        if ($request->getServerParams()['REMOTE_ADDR'] !== $this->_allowedIp) {
            $response = $this->_responseFactory->createResponse(403);
            $response->getBody()->write('Access denied!');
            return $response;
        }

        return $handler->handle($request);
    }
}
