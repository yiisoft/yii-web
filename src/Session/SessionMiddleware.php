<?php
namespace Yiisoft\Yii\Web\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Cookie;

class SessionMiddleware implements MiddlewareInterface
{
    private const COOKIE_NAME = 'sid';

    private const LIFETIME_TWO_WEEKS = 1209600;

    private $cookieTtl = self::LIFETIME_TWO_WEEKS;

    /**
     * @var SessionInterface
     */
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
        if (!$this->session->isStarted()) {
            return $response;
        }

        $this->session->commit();

        $currentSid = $this->session->getID();

        // SID changed, neeed to send new cookie
        if ($this->getSidFromRequest($request) !== $currentSid) {
            $sessionCookie = (new Cookie(self::COOKIE_NAME, $currentSid))
                ->validFor(new \DateInterval('PT' . $this->cookieTtl . 'S'))
                ->path('/')
                ->domain($request->getUri()->getHost())
                ->httpOnly(true)
                ->secure(true);

            return $sessionCookie->addToResponse($response);
        }

        return $response;
    }

    private function getSidFromRequest(ServerRequestInterface $request): ?string
    {
        $cookies = $request->getCookieParams();
        return $cookies[self::COOKIE_NAME] ?? null;
    }
}
