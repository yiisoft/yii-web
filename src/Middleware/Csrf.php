<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Security\Random;
use Yiisoft\Security\TokenMasker;
use Yiisoft\Yii\Web\Cookie;
use Yiisoft\Yii\Web\Session\SessionInterface;

final class Csrf implements MiddlewareInterface
{
    public const CSRF_HEADER = 'X-CSRF-Token';

    private $name = '_csrf';
    private $cookieParams = ['httpOnly' => true];
    private $enableCookie = true;
    private $responseFactory;
    private $session;

    private function __construct(ResponseFactoryInterface $responseFactory, SessionInterface $session)
    {
        $this->responseFactory = $responseFactory;
        $this->session = $session;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->getCsrfToken($request);

        if (empty($token)) {
            $token = Random::string();
        }
        $request = $request->withAttribute('csrf_token', TokenMasker::mask($token));

        try {
            $response = $handler->handle($request);
            $response = $this->addTokenToResponse($response, $token);
        } catch (\Throwable $e) {
            throw new $e;
        }

        return $this->validateCsrfToken($request, $response);
    }

    private function addTokenToResponse(ResponseInterface $response, $token): ResponseInterface
    {

        if ($this->enableCookie) {
            $cookieParameters = \array_merge($this->session->getCookieParameters(), $this->cookieParams);

            $sessionCookie = (new Cookie($this->name, $token))
                ->path($cookieParameters['path'])
                ->domain($cookieParameters['domain'])
                ->httpOnly($cookieParameters['httponly'])
                ->secure($cookieParameters['secure'])
                ->sameSite($cookieParameters['samesite']);

            return $sessionCookie->addToResponse($response);
        } else {
            $this->session->set($this->name, $token);
            return $response;
        }
    }

    private function getCsrfToken(ServerRequestInterface $request): ?string
    {
        if ($this->enableCookie) {
            $cookies = $request->getCookieParams();
            return $cookies[$this->name] ?? null;
        } else {
            return $this->session->get($this->name);
        }
    }

    private function validateCsrfToken(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $trueToken = $this->getCsrfToken($request);
        $method = $request->getMethod();

        if (\in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $response;
        }

        $unmaskedToken = $this->getTokenFromRequest($request);
        if (empty($unmaskedToken) || $trueToken !== $unmaskedToken) {
            $response = $this->responseFactory->createResponse(400);
            $response->getBody()->write('Unable to verify your data submission.');
            return $response;
        }

        return $response;
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
