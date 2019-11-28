<?php
namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Validator\Rule\Ip;

final class IpFilter implements MiddlewareInterface
{
    private $ipValidator;
    private $responseFactory;

    public function __construct(Ip $ipValidator, ResponseFactoryInterface $responseFactory)
    {
        $this->ipValidator = (clone $ipValidator)->disallowSubnet()->disallowNegation();
        $this->responseFactory = $responseFactory;
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
        if (!$this->ipValidator->validateValue($request->getServerParams()['REMOTE_ADDR'])->isValid()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Access denied!');
            return $response;
        }

        return $handler->handle($request);
    }
}
