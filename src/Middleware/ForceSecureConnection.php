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
class ForceSecureConnection implements MiddlewareInterface
{
    protected int $statusCode = Status::MOVED_PERMANENTLY;
    protected ?int $port = null;

    protected bool $addCSP = true;
    protected string $cspDirective = 'upgrade-insecure-requests';

    protected bool $addSTS = true;
    protected int $stsMaxAge = 31_536_000; // 12 months
    protected bool $stsSubDomains = false;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getScheme() === 'http') {
            $url = (string)$request->getUri()->withScheme('https')->withPort($this->port);
            return $this->addCSP(
                $this->responseFactory
                    ->createResponse($this->statusCode)
                    ->withHeader('Location', $url)
            );
        }
        return $this->addSTS($this->addCSP($handler->handle($request)));
    }

    /**
     * Add Content-Security-Policy header
     */
    private function addCSP(ResponseInterface $response): ResponseInterface
    {
        return $this->addCSP
            ? $response->withHeader('Content-Security-Policy', $this->cspDirective)
            : $response;
    }

    /**
     * Add Strict-Transport-Security header
     */
    private function addSTS(ResponseInterface $response): ResponseInterface
    {
        $subDomains = $this->stsSubDomains ? 'includeSubDomains' : '';
        return $this->addSTS
            ? $response->withHeader('Strict-Transport-Security', "max-age={$this->stsMaxAge}; {$subDomains}")
            : $response;
    }
}
