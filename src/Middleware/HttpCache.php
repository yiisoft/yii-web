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
use function in_array;
use function preg_split;
use function reset;
use function str_replace;

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

    /**
     * @var string the name of the cache limiter to be set when [session_cache_limiter()](https://secure.php.net/manual/en/function.session-cache-limiter.php)
     * is called. The default value is an empty string, meaning turning off automatic sending of cache headers entirely.
     * You may set this property to be `public`, `private`, `private_no_expire`, and `nocache`.
     * Please refer to [session_cache_limiter()](https://secure.php.net/manual/en/function.session-cache-limiter.php)
     * for detailed explanation of these values.
     *
     * If this property is `null`, then `session_cache_limiter()` will not be called. As a result,
     * PHP will send headers according to the `session.cache_limiter` PHP ini setting.
     */
    private ?string $sessionCacheLimiter = '';

    /**
     * @var bool a value indicating whether this filter should be enabled.
     */
    private bool $enabled = true;

    private ResponseFactoryInterface $responseFactory;
    private SessionInterface $session;
    private LoggerInterface $logger;

    public function __construct(ResponseFactoryInterface $responseFactory, SessionInterface $session, LoggerInterface $logger)
    {
        $this->responseFactory = $responseFactory;
        $this->session = $session;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        $method = $request->getMethod();
        if (!in_array($method, [Method::GET, Method::HEAD]) || $this->lastModified === null && $this->etagSeed === null) {
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

        $this->sendCacheControlHeader($request);

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
            $response = $response->withHeader('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
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
    private function validateCache(ServerRequestInterface $request, $lastModified, $etag)
    {
        if ($request->hasHeader('If-None-Match')) {
            // HTTP_IF_NONE_MATCH takes precedence over HTTP_IF_MODIFIED_SINCE
            // http://tools.ietf.org/html/rfc7232#section-3.3
            return $etag !== null && in_array($etag, $this->getETags($request), true);
        } elseif ($request->hasHeader('If-Modified-Since')) {
            $header = reset($request->getHeader('If-Modified-Since'));
            return $lastModified !== null && @strtotime($header) >= $lastModified;
        }

        return false;
    }

    /**
     * Sends the cache control header to the client.
     * @param ServerRequestInterface $request
     * @see cacheControlHeader
     */
    private function sendCacheControlHeader(ServerRequestInterface $request): void
    {
        if ($this->sessionCacheLimiter !== null) {
            if ($this->sessionCacheLimiter === '' && !headers_sent() && $this->session->isActive()) {
                header_remove('Expires');
                header_remove('Cache-Control');
                header_remove('Last-Modified');
                header_remove('Pragma');
            }

            $this->setCacheLimiter();
        }
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
            $header = reset($request->getHeader('If-None-Match'));
            return preg_split('/[\s,]+/', str_replace('-gzip', '', $header), -1, PREG_SPLIT_NO_EMPTY);
        }

        return [];
    }

    private function setCacheLimiter()
    {
        if ($this->session->isActive()) {
            if (isset($_SESSION)) {
                $this->sessionData = $_SESSION;
            }
            $this->session->close();
        }

        session_cache_limiter($this->sessionCacheLimiter);

        if (null !== $this->sessionData) {

            $this->session->open();

            $_SESSION = $this->sessionData;
            $this->sessionData = null;
        }
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

    public function setSessionCacheLimiter(?string $cacheLimiter): void
    {
        $this->sessionCacheLimiter = $cacheLimiter;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
