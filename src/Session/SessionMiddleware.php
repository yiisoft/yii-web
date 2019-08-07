<?php
namespace Yiisoft\Yii\Web\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Cookie;

class SessionMiddleware implements MiddlewareInterface
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
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

        $cookieParameters = $this->session->getCookieParameters();

        // SID changed, neeed to send new cookie
        if ($this->getSidFromRequest($request) !== $currentSid) {
            $sessionCookie = (new Cookie($this->session->getName(), $currentSid))
                ->path($cookieParameters['path'])
                ->domain($cookieParameters['domain'] ?? $request->getUri()->getHost())
                ->httpOnly($cookieParameters['httponly'])
                ->secure($cookieParameters['secure'])
                ->sameSite($cookieParameters['samesite'] ?? '');

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
