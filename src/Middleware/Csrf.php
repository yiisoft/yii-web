<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Security\Random;
use Yiisoft\Security\TokenMask;
use Yiisoft\Session\SessionInterface;

final class Csrf implements MiddlewareInterface
{
    private const NAME = '_csrf';
    public const HEADER_NAME = 'X-CSRF-Token';
    public const REQUEST_NAME = 'csrf_token';

    private string $name = self::NAME;
    private string $requestName = self::REQUEST_NAME;
    private ResponseFactoryInterface $responseFactory;
    private SessionInterface $session;

    public function __construct(ResponseFactoryInterface $responseFactory, SessionInterface $session)
    {
        $this->responseFactory = $responseFactory;
        $this->session = $session;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->getToken();

        if (!$this->validateCsrfToken($request, $token)) {
            $this->session->remove($this->name);

            $response = $this->responseFactory->createResponse(Status::UNPROCESSABLE_ENTITY);
            $response->getBody()->write(Status::TEXTS[Status::UNPROCESSABLE_ENTITY]);
            return $response;
        }

        $request = $request->withAttribute($this->requestName, TokenMask::apply($token));

        return $handler->handle($request);
    }

    public function withName(string $name): self
    {
        $new = clone $this;
        $new->name = $name;
        return $new;
    }

    public function withRequestName(string $name): self
    {
        $new = clone $this;
        $new->requestName = $name;
        return $new;
    }

    private function getToken(): ?string
    {
        $token = $this->session->get($this->name);
        if (empty($token)) {
            $token = Random::string();
            $this->session->set($this->name, $token);
        }

        return $token;
    }

    private function validateCsrfToken(ServerRequestInterface $request, ?string $trueToken): bool
    {
        $method = $request->getMethod();

        if (\in_array($method, [Method::GET, Method::HEAD, Method::OPTIONS], true)) {
            return true;
        }

        $unmaskedToken = $this->getTokenFromRequest($request);

        return !empty($unmaskedToken) && hash_equals($unmaskedToken, $trueToken);
    }

    private function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        $parsedBody = $request->getParsedBody();

        $token = $parsedBody[$this->name] ?? null;
        if (empty($token)) {
            $headers = $request->getHeader(self::HEADER_NAME);
            $token = \reset($headers);
        }

        return is_string($token) ? TokenMask::remove($token) : null;
    }
}
