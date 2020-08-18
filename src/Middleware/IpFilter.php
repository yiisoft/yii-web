<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Validator\Rule\Ip;

final class IpFilter implements MiddlewareInterface
{
    private Ip $ipValidator;
    private ResponseFactoryInterface $responseFactory;
    private ?string $clientIpAttribute;

    /**
     * @param Ip $ipValidator Client IP validator. The properties of the validator can be modified up to the moment of processing.
     * @param \Psr\Http\Message\ResponseFactoryInterface $responseFactory
     * @param string|null $clientIpAttribute Attribute name of client IP. If NULL, then 'REMOTE_ADDR' value of the server parameters is processed.
     * If the value is not null, then the attribute specified must have a value, otherwise the request will closed with forbidden.
     */
    public function __construct(Ip $ipValidator, ResponseFactoryInterface $responseFactory, ?string $clientIpAttribute = null)
    {
        $this->ipValidator = $ipValidator;
        $this->responseFactory = $responseFactory;
        $this->clientIpAttribute = $clientIpAttribute;
    }

    public function withIpValidator(Ip $ipValidator): self
    {
        $new = clone $this;
        $new->ipValidator = $ipValidator;
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
        $clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        if ($this->clientIpAttribute !== null) {
            $clientIp = $request->getAttribute($clientIp);
        }
        if ($clientIp === null || !$this->ipValidator->disallowNegation()->disallowSubnet()->validate($clientIp)->isValid()) {
            $response = $this->responseFactory->createResponse(Status::FORBIDDEN);
            $response->getBody()->write(Status::TEXTS[Status::FORBIDDEN]);
            return $response;
        }

        return $handler->handle($request);
    }
}
