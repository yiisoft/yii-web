<?php
namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Router\Method;
use Yiisoft\Yii\Web\Session\SessionInterface;

/**
 * HttpCache implements client-side caching by utilizing the `Last-Modified` and `ETag` HTTP headers.
 */
final class HttpCache implements MiddlewareInterface
{
    private const DEFAULT_HEADER = 'public, max-age=3600';
    /**
     * @internal Frozen session data
     */
    private ?array $sessionData;

    /**
     * @var callable a PHP callback that returns the UNIX timestamp of the last modification time.
     * The callback's signature should be:
     *
     * ```php
     * function ($request, $params)
     * ```
     *
     * where `$request` is the [[ServerRequestInterface]] object that this filter is currently handling;
     * `$params` takes the value of [[params]]. The callback should return a UNIX timestamp.
     *
     * @see http://tools.ietf.org/html/rfc7232#section-2.2
     */
    private $lastModified;

    /**
     * @var callable a PHP callback that generates the ETag seed string.
     * The callback's signature should be:
     *
     * ```php
     * function ($request, $params)
     * ```
     *
     * where `$request` is the [[ServerRequestInterface]] object that this filter is currently handling;
     * `$params` takes the value of [[params]]. The callback should return a string serving
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
     * @var mixed additional parameters that should be passed to the [[lastModified]] and [[etagSeed]] callbacks.
     */
    private $params;

    /**
     * @var string the value of the `Cache-Control` HTTP header. If null, the header will not be sent.
     * @see http://tools.ietf.org/html/rfc2616#section-14.9
     */
    private ?string $cacheControlHeader = self::DEFAULT_HEADER;

    private ResponseFactoryInterface $responseFactory;
    private SessionInterface $session;
    private LoggerInterface $logger;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        SessionInterface $session,
        LoggerInterface $logger
    ) {
        $this->responseFactory = $responseFactory;
        $this->session = $session;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        if (
            !\in_array($method, [Method::GET, Method::HEAD])
            || ($this->lastModified === null && $this->etagSeed === null)
        ) {
            return $handler->handle($request);
        }

        $lastModified = $etag = null;
        if ($this->lastModified !== null) {
            $lastModified = call_user_func($this->lastModified, $request, $this->params);
        }
        if ($this->etagSeed !== null) {
            $seed = call_user_func($this->etagSeed, $request, $this->params);
            if ($seed !== null) {
                $etag = $this->generateEtag($seed);
            }
        }

        $response = $handler->handle($request);
        if ($this->cacheControlHeader !== null) {
            $response = $response->withHeader('Cache-Control', $this->cacheControlHeader);
        }
        if ($etag !== null) {
            $response = $response->withHeader('Etag', $etag);
        }

        $cacheValid = $this->validateCache($request, $lastModified, $etag);
        // https://tools.ietf.org/html/rfc7232#section-4.1
        if ($lastModified !== null && (!$cacheValid || ($cacheValid && $etag === null))) {
            $response = $response->withHeader(
                'Last-Modified',
                gmdate('D, d M Y H:i:s', $lastModified) . ' GMT'
            );
        }
        if ($cacheValid) {
            $response = $this->responseFactory->createResponse(304);
            $response->getBody()->write('Not Modified');
            return $response;
        }
        return $response;
    }

    /**
     * Validates if the HTTP cache contains valid content.
     * If both Last-Modified and ETag are null, returns false.
     * @param ServerRequestInterface $request
     * @param int $lastModified the calculated Last-Modified value in terms of a UNIX timestamp.
     * If null, the Last-Modified header will not be validated.
     * @param string $etag the calculated ETag value. If null, the ETag header will not be validated.
     * @return bool whether the HTTP cache is still valid.
     */
    private function validateCache(ServerRequestInterface $request, $lastModified, $etag): bool
    {
        if ($request->hasHeader('If-None-Match')) {
            // HTTP_IF_NONE_MATCH takes precedence over HTTP_IF_MODIFIED_SINCE
            // http://tools.ietf.org/html/rfc7232#section-3.3
            return $etag !== null && \in_array($etag, $this->getETags($request), true);
        }

        if ($request->hasHeader('If-Modified-Since')) {
            $headers = $request->getHeader('If-Modified-Since');
            $header = \reset($headers);
            return $lastModified !== null && @strtotime($header) >= $lastModified;
        }

        return false;
    }

    /**
     * Generates an ETag from the given seed string.
     * @param string $seed Seed for the ETag
     * @return string the generated ETag
     */
    private function generateEtag($seed): string
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
            $headers = $request->getHeader('If-None-Match');
            $header = \str_replace('-gzip', '', \reset($headers));
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
