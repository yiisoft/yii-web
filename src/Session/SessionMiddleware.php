<?php

namespace Yiisoft\Yii\Web\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Cookie;

class SessionMiddleware implements MiddlewareInterface
{
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestSessionId = $this->getSidFromRequest($request);
        if ($requestSessionId !== null) {
            $this->session->setId($requestSessionId);
        }

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            $this->session->discard();
            throw $e;
        }

        return $this->commitSession($request, $response);
    }

    private function commitSession(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->session->isActive()) {
            return $response;
        }

        $this->session->close();

        $currentSid = $this->session->getID();

        // SID changed, neeed to send new cookie
        if ($this->getSidFromRequest($request) !== $currentSid) {
            $cookieParameters = $this->session->getCookieParameters();

            $cookieDomain = $cookieParameters['domain'];
            if (empty($cookieDomain)) {
                $cookieDomain = $request->getUri()->getHost();
            }

            $useSecureCookie = $cookieParameters['secure'];
            if ($useSecureCookie && $request->getUri()->getScheme() !== 'https') {
                throw new SessionException('"cookie_secure" is on but connection is not secure. Either set Session "cookie_secure" option to "0" or make connection secure');
            }

            $sessionCookie = (new Cookie($this->session->getName(), $currentSid))
                ->path($cookieParameters['path'])
                ->domain($cookieDomain)
                ->httpOnly($cookieParameters['httponly'])
                ->secure($useSecureCookie)
                ->sameSite($cookieParameters['samesite'] ?? Cookie::SAME_SITE_LAX);

            if ($cookieParameters['lifetime'] > 0) {
                $sessionCookie = $sessionCookie->validFor(new \DateInterval('PT' . $cookieParameters['lifetime'] . 'S'));
            }

            return $sessionCookie->addToResponse($response);
        }

        return $response;
    }

    private function getSidFromRequest(ServerRequestInterface $request): ?string
    {
        $cookies = $request->getCookieParams();
        return $cookies[$this->session->getName()] ?? null;
    }
}
