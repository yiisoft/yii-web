<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\Method;
use Yiisoft\Security\Random;
use Yiisoft\Security\TokenMasker;
use Yiisoft\Yii\Web\Session\SessionInterface;

final class Csrf implements MiddlewareInterface
{
    public const NAME = '_csrf';
    public const HEADER_NAME = 'X-CSRF-Token';
    public const REQUEST_NAME = 'csrf_token';

    private $name = self::NAME;
    private $requestName = self::REQUEST_NAME;
    private $responseFactory;
    private $session;

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

            $response = $this->responseFactory->createResponse(400);
            $response->getBody()->write('Unable to verify your data submission.');
            return $response;
        }

        $request = $request->withAttribute($this->requestName, TokenMasker::mask($token));

        return $handler->handle($request);
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setRequestName(string $name): void
    {
        $this->requestName = $name;
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

        return TokenMasker::unmask($token);
    }
}
