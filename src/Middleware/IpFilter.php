<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\NetworkResolver\BasicNetworkResolver;
use Yiisoft\Yii\Web\NetworkResolver\NetworkResolverInterface;

final class IpFilter implements MiddlewareInterface
{
    private $allowedIp;
    private $responseFactory;
    /**
     * If not configured, then BasicNetworkResolver is used.
     * @var NetworkResolverInterface
     */
    private $networkResolver;

    public function __construct(string $allowedIp, ResponseFactoryInterface $responseFactory)
    {
        $this->allowedIp = $allowedIp;
        $this->responseFactory = $responseFactory;
        $this->networkResolver = new BasicNetworkResolver();
    }

    /**
     * @return static
     */
    public function withNetworkResolver(NetworkResolverInterface $networkResolver)
    {
        $new = clone $this;
        $new->networkResolver = $networkResolver;
        return $new;
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
        $networkResolver = $this->networkResolver->withServerRequest($request);
        if ($networkResolver->getUserIp() !== $this->allowedIp) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Access denied!');
            return $response;
        }

        return $handler->handle($request);
    }
}
