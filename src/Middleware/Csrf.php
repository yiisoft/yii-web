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
use Yiisoft\Yii\Web\Cookie;
use Yiisoft\Yii\Web\Session\SessionInterface;

final class Csrf implements MiddlewareInterface
{
    public const CSRF_HEADER = 'X-CSRF-Token';

    private $name = '_csrf';
    private $cookieParams = ['httpOnly' => true];
    private $responseFactory;
    private $session;

    public function __construct(ResponseFactoryInterface $responseFactory, SessionInterface $session)
    {
        $this->responseFactory = $responseFactory;
        $this->session = $session;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->getCsrfToken($request);

        if (!$this->validateCsrfToken($request, $token)) {
            $response = $this->responseFactory->createResponse(400);
            $response->getBody()->write('Unable to verify your data submission.');
            return $response;
        }

        if (empty($token)) {
            $token = Random::string();
        }

        $request = $request->withAttribute('csrf_token', TokenMasker::mask($token));

        $response = $handler->handle($request);
        $response = $this->addTokenToResponse($response, $token);

        return $response;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setCookieParams(array $params): void
    {
        $this->cookieParams = $params;
    }

    private function addTokenToResponse(ResponseInterface $response, string $token): ResponseInterface
    {

        $cookieParameters = \array_merge($this->session->getCookieParameters(), $this->cookieParams);

        $sessionCookie = (new Cookie($this->name, $token))
            ->path($cookieParameters['path'])
            ->domain($cookieParameters['domain'])
            ->httpOnly($cookieParameters['httponly'])
            ->secure($cookieParameters['secure'])
            ->sameSite($cookieParameters['samesite']);

        return $sessionCookie->addToResponse($response);
    }

    private function getCsrfToken(ServerRequestInterface $request): ?string
    {
        $cookies = $request->getCookieParams();
        return $cookies[$this->name] ?? null;
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
            $headers = $request->getHeader(self::CSRF_HEADER);
            $token = \reset($headers);
        }

        return TokenMasker::unmask($token);
    }
}
