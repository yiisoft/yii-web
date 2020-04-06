<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

/**
 * Redirects from HTTP to HTTPS and adds CSP and HSTS headers
 */
final class ForceSecureConnection implements MiddlewareInterface
{
    private bool $redirect = true;
    private int $statusCode = Status::MOVED_PERMANENTLY;
    private ?int $port = null;

    private bool $addCSP = true;
    private string $cspDirectives = self::DEFAULT_CSP_DIRECTIVES;

    private bool $addSTS = true;
    private int $hstsMaxAge = self::DEFAULT_HSTS_MAX_AGE;
    private bool $hstsSubDomains = false;

    private ResponseFactoryInterface $responseFactory;
    private const DEFAULT_CSP_DIRECTIVES = 'upgrade-insecure-requests; default-src https:';
    private const DEFAULT_HSTS_MAX_AGE = 31_536_000; // 12 months

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->redirect && strcasecmp($request->getUri()->getScheme(), 'http') === 0) {
            $url = (string)$request->getUri()->withScheme('https')->withPort($this->port);
            return $this->addCSP(
                $this->responseFactory
                    ->createResponse($this->statusCode)
                    ->withHeader('Location', $url)
            );
        }
        return $this->addHSTS($this->addCSP($handler->handle($request)));
    }

    public function withRedirection($statusCode = Status::MOVED_PERMANENTLY, int $port = null): self
    {
        $clone = clone $this;
        $clone->redirect = true;
        $clone->port = $port;
        $clone->statusCode = $statusCode;
        return $clone;
    }
    public function withoutRedirection(): self
    {
        $clone = clone $this;
        $clone->redirect = false;
        return $clone;
    }

    /**
     * Add Content-Security-Policy header to Response
     * @link https://developer.mozilla.org/docs/Web/HTTP/CSP
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Content-Security-Policy
     * @param string $directives
     * @return ForceSecureConnection
     */
    public function withCSP(string $directives = self::DEFAULT_CSP_DIRECTIVES): self
    {
        $clone = clone $this;
        $clone->addCSP = true;
        $clone->cspDirectives = $directives;
        return $clone;
    }
    public function withoutCSP(): self
    {
        $clone = clone $this;
        $clone->addCSP = false;
        return $clone;
    }

    /**
     * Add Strict-Transport-Security header to Response
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Strict-Transport-Security
     * @param int $maxAge
     * @param bool $subDomains
     * @return ForceSecureConnection
     */
    public function withHSTS(int $maxAge = self::DEFAULT_HSTS_MAX_AGE, bool $subDomains = false): self
    {
        $clone = clone $this;
        $clone->addSTS = true;
        $clone->hstsMaxAge = $maxAge;
        $clone->hstsSubDomains = $subDomains;
        return $clone;
    }
    public function withoutHSTS(): self
    {
        $clone = clone $this;
        $clone->addSTS = false;
        return $clone;
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    private function addCSP(ResponseInterface $response): ResponseInterface
    {
        return $this->addCSP
            ? $response->withHeader('Content-Security-Policy', $this->cspDirectives)
            : $response;
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    private function addHSTS(ResponseInterface $response): ResponseInterface
    {
        $subDomains = $this->hstsSubDomains ? 'includeSubDomains' : '';
        return $this->addSTS
            ? $response->withHeader('Strict-Transport-Security', "max-age={$this->hstsMaxAge}; {$subDomains}")
            : $response;
    }
}
