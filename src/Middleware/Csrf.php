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
    public const HEADER_NAME = 'X-CSRF-Token';
    public const SESSION_NAME = '_csrf';
    public const REQUEST_NAME = 'csrf_token';

    private $name = self::SESSION_NAME;
    private $requestParam = self::REQUEST_NAME;
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

        $this->session->set($this->name, $token);

        $request = $request->withAttribute($this->requestParam, TokenMasker::mask($token));
        $response = $handler->handle($request);

        return $response;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setRequestParam(string $name): void
    {
        $this->requestParam = $name;
    }

    private function getToken(): ?string
    {
        $token = $this->session->get($this->name);
        if (!empty($token)) {
            return $token;
        } else {
            return Random::string();
        }
    }

    private function validateCsrfToken(ServerRequestInterface $request, ?string $trueToken): bool
    {
        $method = $request->getMethod();

        if (\in_array($method, [Method::GET, Method::HEAD, Method::OPTIONS], true)) {
            return true;
        }

        $unmaskedToken = $this->getTokenFromRequest($request);

        if (empty($unmaskedToken) || !hash_equals($unmaskedToken, $trueToken)) {
            return false;
        }

        return true;
    }

    private function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        $post = $request->getParsedBody();

        $token = null;
        if (isset($post[$this->name])) {
            $token = $post[$this->name];
        }

        if ($token === null) {
            $headers = $request->getHeader(self::HEADER_NAME);
            $token = \reset($headers);
        }

        return TokenMasker::unmask($token);
    }
}
