<?php


namespace Yiisoft\Yii\Web\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\NetworkResolver\NetworkResolverInterface;

class NetworkResolver implements MiddlewareInterface
{
    /**
     * @var NetworkResolverInterface
     */
    private $networkResolver;

    public function __construct(NetworkResolverInterface $networkResolver)
    {
        $this->networkResolver = $networkResolver;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $nr = $this->networkResolver->withServerRequest($request);
        $uri = $request->getUri()->withScheme($nr->getRequestScheme());

        return $handler->handle($request->withUri($uri));
    }
}
