<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;

/**
 * HttpCache implements client-side caching by utilizing the `Last-Modified` and `ETag` HTTP headers.
 */
final class HttpCache implements MiddlewareInterface
{
    private const DEFAULT_HEADER = 'public, max-age=3600';

    /**
     * @var callable a PHP callback that returns the UNIX timestamp of the last modification time.
     * The callback's signature should be:
     *
     * ```php
     * function (ServerRequestInterface $request, $params): int
     * ```
     *
     * where `$request` is the {@see ServerRequestInterface} object that this filter is currently handling;
     * `$params` takes the value of {@see params}. The callback should return a UNIX timestamp.
     *
     * @see http://tools.ietf.org/html/rfc7232#section-2.2
     */
    private $lastModified;

    /**
     * @var callable a PHP callback that generates the ETag seed string.
     * The callback's signature should be:
     *
     * ```php
     * function (ServerRequestInterface $request, $params): string
     * ```
     *
     * where `$request` is the {@see ServerRequestInterface} object that this middleware is currently handling;
     * `$params` takes the value of {@see $params}. The callback should return a string serving
     * as the seed for generating an ETag.
     */
    private $etagSeed;

    /**
     * @var bool whether to generate weak ETags.
     *
     * Weak ETags should be used if the content should be considered semantically equivalent, but not byte-equal.
     *
     * @see http://tools.ietf.org/html/rfc7232#section-2.3
     */
    private bool $weakEtag = false;

    /**
     * @var mixed additional parameters that should be passed to the {@see $lastModified} and {@see etagSeed} callbacks.
     */
    private $params;

    /**
     * @var string the value of the `Cache-Control` HTTP header. If null, the header will not be sent.
     * @see http://tools.ietf.org/html/rfc2616#section-14.9
     */
    private ?string $cacheControlHeader = self::DEFAULT_HEADER;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            ($this->lastModified === null && $this->etagSeed === null) ||
            !\in_array($request->getMethod(), [Method::GET, Method::HEAD], true)
        ) {
            return $handler->handle($request);
        }

        $lastModified = null;
        if ($this->lastModified !== null) {
            $lastModified = call_user_func($this->lastModified, $request, $this->params);
        }

        $etag = null;
        if ($this->etagSeed !== null) {
            $seed = call_user_func($this->etagSeed, $request, $this->params);
            if ($seed !== null) {
                $etag = $this->generateEtag($seed);
            }
        }

        $cacheIsValid = $this->validateCache($request, $lastModified, $etag);
        if ($cacheIsValid) {
            return $this->responseFactory
                ->createResponse(Status::NOT_MODIFIED)
                ->withHeader(
                    'Last-Modified',
                    gmdate('D, d M Y H:i:s', $lastModified) . ' GMT'
                );
        }

        $response = $handler->handle($request);
        if ($this->cacheControlHeader !== null) {
            $response = $response->withHeader('Cache-Control', $this->cacheControlHeader);
        }
        if ($etag !== null) {
            $response = $response->withHeader('Etag', $etag);
        }

        // https://tools.ietf.org/html/rfc7232#section-4.1
        if ($lastModified !== null && (!$cacheIsValid || ($cacheIsValid && $etag === null))) {
            $response = $response->withHeader(
                'Last-Modified',
                gmdate('D, d M Y H:i:s', $lastModified) . ' GMT'
            );
        }

        return $response;
    }

    /**
     * Validates if the HTTP cache contains valid content.
     * If both Last-Modified and ETag are null, returns false.
     * @param ServerRequestInterface $request
     * @param int|null $lastModified the calculated Last-Modified value in terms of a UNIX timestamp.
     * If null, the Last-Modified header will not be validated.
     * @param string|null $etag the calculated ETag value. If null, the ETag header will not be validated.
     * @return bool whether the HTTP cache is still valid.
     */
    private function validateCache(ServerRequestInterface $request, ?int $lastModified, ?string $etag): bool
    {
        if ($request->hasHeader('If-None-Match')) {
            // HTTP_IF_NONE_MATCH takes precedence over HTTP_IF_MODIFIED_SINCE
            // http://tools.ietf.org/html/rfc7232#section-3.3
            return $etag !== null && \in_array($etag, $this->getETags($request), true);
        }

        if ($request->hasHeader('If-Modified-Since')) {
            $header = $request->getHeaderLine('If-Modified-Since');
            return $lastModified !== null && @strtotime($header) >= $lastModified;
        }

        return false;
    }

    /**
     * Generates an ETag from the given seed string.
     * @param string $seed Seed for the ETag
     * @return string the generated ETag
     */
    private function generateEtag(string $seed): string
    {
        $etag = '"' . rtrim(base64_encode(sha1($seed, true)), '=') . '"';
        return $this->weakEtag ? 'W/' . $etag : $etag;
    }

    /**
     * Gets the Etags.
     *
     * @param ServerRequestInterface $request
     * @return array The entity tags
     */
    private function getETags(ServerRequestInterface $request): array
    {
        if ($request->hasHeader('If-None-Match')) {
            $header = $request->getHeaderLine('If-None-Match');
            $header = \str_replace('-gzip', '', $header);
            return \preg_split('/[\s,]+/', $header, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return [];
    }

    public function setLastModified(callable $lastModified): void
    {
        $this->lastModified = $lastModified;
    }

    public function setEtagSeed(callable $etagSeed): void
    {
        $this->etagSeed = $etagSeed;
    }

    public function setWeakTag(bool $weakTag): void
    {
        $this->weakEtag = $weakTag;
    }

    public function setParams($params): void
    {
        $this->params = $params;
    }

    public function setCacheControlHeader(?string $header): void
    {
        $this->cacheControlHeader = $header;
    }
}
