<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\NetworkUtilities\IpHelper;
use Yiisoft\Validator\Rule\Ip;
use Yiisoft\Yii\Web\Helper\HeaderHelper;

/**
 * Trusted hosts network resolver
 *
 * Code example with comments:
 * ```
 * (new TrustedHostsNetworkResolver($responseFactory))
 * ->withAddedTrustedHosts(
 *   // List of secure hosts including $ _SERVER['REMOTE_ADDR'], can specify IPv4, IPv6, domains and aliases (see {{Ip}})
 *   ['1.1.1.1', '2.2.2.1/3', '2001::/32', 'localhost']
 *   // IP list headers. For advanced handling headers, see the constants IP_HEADER_TYPE_ *.
 *   // Headers containing multiple sub-elements (eg RFC 7239) must also be listed for other relevant types
 *   // (eg. host headers), otherwise they will only be used as an IP list.
 *   ['x-forwarded-for', [TrustedHostsNetworkResolver::IP_HEADER_TYPE_RFC7239, 'forwarded']]
 *   // protocol headers with accepted protocols and values. Matching of values ​​is case insensitive.
 *   ['front-end-https' => ['https' => 'on']],
 *   // Host headers
 *   ['forwarded', 'x-forwarded-for']
 *   // URL headers
 *   ['x-rewrite-url'],
 *   // Trusted headers. It is a good idea to list all relevant headers.
 *   ['x-forwarded-for', 'forwarded', ...]
 * );
 * ->withAddedTrustedHosts(...)
 * // If the network is not trusted, you can continue the process through the specified middleware.
 * // Otherwise, the process ends with a status code of 412.
 * ->withNotTrustedBranch($middleware)
 * ;
 * ```
 *
 */
class TrustedHostsNetworkResolver implements MiddlewareInterface
{
    public const IP_HEADER_TYPE_RFC7239 = 'rfc7239';

    public const DEFAULT_TRUSTED_HEADERS = [
        // common:
        'x-forwarded-for',
        'x-forwarded-host',
        'x-forwarded-proto',

        // RFC:
        'forward',

        // Microsoft:
        'front-end-https',
        'x-rewrite-url',
    ];

    private const DATA_KEY_HOSTS = 'hosts';
    private const DATA_KEY_IP_HEADERS = 'ipHeaders';
    private const DATA_KEY_HOST_HEADERS = 'hostHeaders';
    private const DATA_KEY_URL_HEADERS = 'urlHeaders';
    private const DATA_KEY_PROTOCOL_HEADERS = 'protocolHeaders';
    private const DATA_KEY_TRUSTED_HEADERS = 'trustedHeaders';

    private $trustedHosts = [];

    /**
     * @var string|null
     */
    private $attributeIps;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;
    /**
     * @var Chain|null
     */
    private $notTrustedBranch;

    /**
     * @var Ip|null
     */
    private $ipValidator;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @return static
     */
    public function withIpValidator(Ip $ipValidator)
    {
        $new = clone $this;
        $ipValidator = clone $ipValidator;
        // force disable unacceptable validation
        $new->ipValidator = $ipValidator->disallowSubnet()->disallowNegation();
        return $new;
    }

    /**
     * If the network is not trusted, you can continue the process through the specified middleware.
     * Otherwise, the process ends with a status code of 412.
     * @return static
     */
    public function withNotTrustedBranch(?MiddlewareInterface $middleware)
    {
        $new = clone $this;
        $new->notTrustedBranch = $middleware;
        return $new;
    }

    /**
     * With added trusted hosts and related headers
     *
     * The header lists are evaluated in the order they were specified.
     * If you specify multiple headers by type (eg IP headers), you must ensure that the irrelevant header is removed
     * eg. web server application, otherwise spoof clients can be use this vulnerability.
     *
     * @param string[] $hosts List of trusted hosts IP addresses. If `isValidHost` is extended, then can use
     *                        domain names with reverse DNS resolving eg. yiiframework.com, * .yiiframework.com.
     * @param array $ipHeaders List of headers containing IP lists.
     * @param array $protocolHeaders List of headers containing protocol. eg. ['x-forwarded-for' => ['http' => 'http', 'https' => ['on', 'https']]]
     * @param string[] $hostHeaders List of headers containing HTTP host.
     * @param string[] $urlHeaders List of headers containing HTTP URL.
     * @param string[]|null $trustedHeaders List of trusted headers. Removed from the request, if in checking process
     *                                      are classified as untrusted by hosts.
     * @return static
     */
    public function withAddedTrustedHosts(
        array $hosts,
        // Defining default headers is not secure!
        array $ipHeaders = [],
        array $protocolHeaders = [],
        array $hostHeaders = [],
        array $urlHeaders = [],
        ?array $trustedHeaders = null
    ) {
        $new = clone $this;
        foreach ($ipHeaders as $ipHeader) {
            if (\is_string($ipHeader)) {
                continue;
            }
            if (!\is_array($ipHeader)) {
                throw new \InvalidArgumentException('Type of ipHeader is not a string and not array');
            }
            if (count($ipHeader) !== 2) {
                throw new \InvalidArgumentException('The ipHeader array must have exactly 2 elements');
            }
            [$type, $header] = $ipHeader;
            if (!\is_string($type)) {
                throw new \InvalidArgumentException('The type is not a string');
            }
            if (!\is_string($header)) {
                throw new \InvalidArgumentException('The header is not a string');
            }
            if ($type === self::IP_HEADER_TYPE_RFC7239) {
                continue;
            }

            throw new \InvalidArgumentException("Not supported IP header type: $type");
        }
        if (count($hosts) === 0) {
            throw new \InvalidArgumentException("Empty hosts not allowed");
        }
        $data = [
            self::DATA_KEY_HOSTS => $hosts,
            self::DATA_KEY_IP_HEADERS => $ipHeaders,
            self::DATA_KEY_PROTOCOL_HEADERS => $this->prepareProtocolHeaders($protocolHeaders),
            self::DATA_KEY_TRUSTED_HEADERS => $trustedHeaders ?? self::DEFAULT_TRUSTED_HEADERS,
            self::DATA_KEY_HOST_HEADERS => $hostHeaders,
            self::DATA_KEY_URL_HEADERS => $urlHeaders,
        ];
        foreach ([
                     self::DATA_KEY_HOSTS,
                     self::DATA_KEY_TRUSTED_HEADERS,
                     self::DATA_KEY_HOST_HEADERS,
                     self::DATA_KEY_URL_HEADERS
                 ] as $key) {
            $this->checkStringArrayType($data[$key], $key);
        }
        foreach ($data[self::DATA_KEY_HOSTS] as $host) {
            $host = str_replace('*', 'wildcard', $host);        // wildcard is allowed in host
            if (filter_var($host, FILTER_VALIDATE_DOMAIN) === false) {
                throw new \InvalidArgumentException("'$host' host is not a domain and not an IP address");
            }
        }
        $new->trustedHosts[] = $data;
        return $new;
    }

    private function checkStringArrayType(array $array, string $field): void
    {
        foreach ($array as $item) {
            if (!is_string($item)) {
                throw new \InvalidArgumentException("$field must be string type");
            }
            if (trim($item) === '') {
                throw new \InvalidArgumentException("$field cannot be empty strings");
            }
        }
    }

    /**
     * @return static
     */
    public function withoutTrustedHosts()
    {
        $new = clone $this;
        $new->trustedHosts = [];
        return $new;
    }

    /**
     * Request's attribute name to which trusted path data is added.
     *
     * The list starts with the server and the last item is the client itself.
     *
     * @return static
     * @see getElementsByRfc7239
     */
    public function withAttributeIps(?string $attribute)
    {
        if ($attribute === '') {
            throw new \RuntimeException('Attribute should not be empty');
        }
        $new = clone $this;
        $new->attributeIps = $attribute;
        return $new;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $actualHost = $request->getServerParams()['REMOTE_ADDR'];
        $trustedHostData = null;
        $trustedHeaders = [];
        $ipValidator = $this->ipValidator ?? new Ip();
        foreach ($this->trustedHosts as $data) {
            // collect all trusted headers
            $trustedHeaders = array_merge($trustedHeaders, $data[self::DATA_KEY_TRUSTED_HEADERS]);
            if ($trustedHostData !== null) {
                // trusted hosts already found
                continue;
            }
            if ($this->isValidHost($actualHost, $data[self::DATA_KEY_HOSTS], $ipValidator)) {
                $trustedHostData = $data;
            }
        }
        $untrustedHeaders = array_diff($trustedHeaders, $trustedHostData[self::DATA_KEY_TRUSTED_HEADERS] ?? []);
        $request = $this->removeHeaders($request, $untrustedHeaders);
        if ($trustedHostData === null) {
            // No trusted host at all.
            if ($this->notTrustedBranch !== null) {
                return $this->notTrustedBranch->process($request, $handler);
            }
            $response = $this->responseFactory->createResponse(412);
            $response->getBody()->write('Unable to verify your network.');
            return $response;
        }
        [$ipListType, $ipHeader, $ipList] = $this->getIpList($request, $trustedHostData[self::DATA_KEY_IP_HEADERS]);
        $ipList = array_reverse($ipList);       // the first item should be the closest to the server
        if ($ipListType === null) {
            $ipList = $this->getFormattedIpList($ipList);
        } elseif ($ipListType === self::IP_HEADER_TYPE_RFC7239) {
            $ipList = $this->getElementsByRfc7239($ipList);
        }
        array_unshift($ipList, ['ip' => $actualHost]);  // server's ip to first position
        $ipDataList = [];
        do {
            $ipData = array_shift($ipList);
            if (!isset($ipData['ip'])) {
                $ipData = $this->reverseObfuscate($ipData, $ipDataList, $ipList, $request);
                if (!isset($ipData['ip'])) {
                    break;
                }
            }
            $ip = $ipData['ip'];
            if (!$this->isValidHost($ip, ['any'], $ipValidator)) {
                break;
            }
            $ipDataList[] = $ipData;
            if (!$this->isValidHost($ip, $trustedHostData[self::DATA_KEY_HOSTS], $ipValidator)) {
                break;
            }
        } while (count($ipList) > 0);

        if ($this->attributeIps !== null) {
            $request = $request->withAttribute($this->attributeIps, $ipDataList);
        }

        $uri = $request->getUri();
        // find HTTP host
        foreach ($trustedHostData[self::DATA_KEY_HOST_HEADERS] as $hostHeader) {
            if (!$request->hasHeader($hostHeader)) {
                continue;
            }
            if ($hostHeader === $ipHeader && $ipListType === self::IP_HEADER_TYPE_RFC7239 && isset($ipData['httpHost'])) {
                $uri = $uri->withHost($ipData['httpHost']);
                break;
            }
            $host = $request->getHeaderLine($hostHeader);
            if (filter_var($host, FILTER_VALIDATE_DOMAIN) !== false) {
                $uri = $uri->withHost($host);
                break;
            }
        }

        // find protocol
        foreach ($trustedHostData[self::DATA_KEY_PROTOCOL_HEADERS] as $protocolHeader => $protocols) {
            if (!$request->hasHeader($protocolHeader)) {
                continue;
            }
            if ($protocolHeader === $ipHeader && $ipListType === self::IP_HEADER_TYPE_RFC7239 && isset($ipData['protocol'])) {
                $uri = $uri->withScheme($ipData['protocol']);
                break;
            }
            $protocolHeaderValue = $request->getHeaderLine($protocolHeader);
            foreach ($protocols as $protocol => $acceptedValues) {
                if (\in_array($protocolHeaderValue, $acceptedValues)) {
                    $uri = $uri->withScheme($protocol);
                    break 2;
                }
            }
        }
        $urlParts = $this->getUrl($request, $trustedHostData[self::DATA_KEY_URL_HEADERS]);
        if ($urlParts !== null) {
            [$path, $query] = $urlParts;
            $uri = $uri->withPath($path);
            if ($query !== null) {
                $uri = $uri->withQuery($query);
            }
        }
        return $handler->handle($request->withUri($uri)->withAttribute('requestClientIp', $ipData['ip']));
    }

    /**
     * Validate host by range
     *
     * This method can be extendable by overwriting eg. with reverse DNS verification.
     */
    protected function isValidHost(string $host, array $ranges, Ip $validator): bool
    {
        return $validator->ranges($ranges)->validate($host)->isValid();
    }

    /**
     * Reverse obfuscating host data
     *
     * The base operation does not perform any transformation on the data.
     * This method can be extendable by overwriting eg.
     *
     * The `$ipData` array must contain relevant keys (eg` ip`) for the validation process ({{isValidHost}}) to process the request.
     *
     * @see getElementsByRfc7239
     */
    protected function reverseObfuscate(
        array $ipData,
        array $ipDataListValidated,
        array $ipDataListRemaining,
        RequestInterface $request
    ): array {
        return $ipData;
    }

    private function prepareProtocolHeaders(array $protocolHeaders): array
    {
        $output = [];
        foreach ($protocolHeaders as $header => $protocolAndAcceptedValues) {
            $header = strtolower($header);
            if (\is_callable($protocolAndAcceptedValues)) {
                $output[$header] = $protocolAndAcceptedValues;
                continue;
            }
            if (!\is_array($protocolAndAcceptedValues)) {
                throw new \RuntimeException('Accepted values is not an array nor callable');
            }
            if (count($protocolAndAcceptedValues) === 0) {
                throw new \RuntimeException('Accepted values cannot be an empty array');
            }
            $output[$header] = [];
            foreach ($protocolAndAcceptedValues as $protocol => $acceptedValues) {
                if (!\is_string($protocol)) {
                    throw new \RuntimeException('The protocol must be a string');
                }
                if ($protocol === '') {
                    throw new \RuntimeException('The protocol cannot be empty');
                }
                $output[$header][$protocol] = array_map('strtolower', (array)$acceptedValues);
            }
        }
        return $output;
    }

    private function removeHeaders(ServerRequestInterface $request, array $headers): ServerRequestInterface
    {
        foreach ($headers as $header) {
            $request = $request->withoutAttribute($header);
        }
        return $request;
    }

    private function getIpList(RequestInterface $request, array $ipHeaders): array
    {
        foreach ($ipHeaders as $ipHeader) {
            $type = null;
            if (\is_array($ipHeader)) {
                $type = array_shift($ipHeader);
                $ipHeader = array_shift($ipHeader);
            }
            if ($request->hasHeader($ipHeader)) {
                return [$type, $ipHeader, $request->getHeader($ipHeader)];
            }
        }
        return [null, null, []];
    }

    /**
     * @see getElementsByRfc7239
     */
    private function getFormattedIpList(array $forwards): array
    {
        $list = [];
        foreach ($forwards as $ip) {
            $list[] = ['ip' => $ip];
        }
        return $list;
    }

    /**
     * Forwarded elements by RFC7239
     *
     * The structure of the elements:
     * - `host`: IP or obfuscated hostname or "unknown"
     * - `ip`: IP address (only if presented)
     * - `by`: used user-agent by proxy (only if presented)
     * - `port`: port number received by proxy (only if presented)
     * - `protocol`: protocol received by proxy (only if presented)
     * - `httpHost`: HTTP host received by proxy (only if presented)
     *
     * @link https://tools.ietf.org/html/rfc7239
     * @return array proxy data elements
     */
    private function getElementsByRfc7239(array $forwards): array
    {
        $list = [];
        foreach ($forwards as $forward) {
            $data = HeaderHelper::getParameters($forward);
            if (!isset($data['for'])) {
                // Invalid item, the following items will be dropped
                break;
            }
            $pattern = '/^(?<host>' . IpHelper::IPV4_PATTERN . '|unknown|_[\w\.-]+|[[]' . IpHelper::IPV6_PATTERN . '[]])(?::(?<port>[\w\.-]+))?$/';
            if (preg_match($pattern, $data['for'], $matches) === 0) {
                // Invalid item, the following items will be dropped
                break;
            }
            $ipData = [];
            $host = $matches['host'];
            $obfuscatedHost = $host === 'unknown' || strpos($host, '_') === 0;
            if (!$obfuscatedHost) {
                // IPv4 & IPv6
                $ipData['ip'] = strpos($host, '[') === 0 ? trim($host /* IPv6 */, '[]') : $host;
            }
            $ipData['host'] = $host;
            if (isset($matches['port'])) {
                $port = $matches['port'];
                if (!$obfuscatedHost && (preg_match('/^\d{1,5}$/', $port) === 0 || (int)$port > 65535)) {
                    // Invalid port, the following items will be dropped
                    break;
                }
                $ipData['port'] = $obfuscatedHost ? $port : (int)$port;
            }

            // copy other properties
            foreach (['proto' => 'protocol', 'host' => 'httpHost', 'by' => 'by'] as $source => $destination) {
                if (isset($data[$source])) {
                    $ipData[$destination] = $data[$source];
                }
            }
            if (isset($ipData['httpHost']) && filter_var($ipData['httpHost'], FILTER_VALIDATE_DOMAIN) === false) {
                // remove not valid HTTP host
                unset($ipData['httpHost']);
            }

            $list[] = $ipData;
        }
        return $list;
    }

    private function getUrl(RequestInterface $request, array $urlHeaders): ?array
    {
        foreach ($urlHeaders as $header) {
            if (!$request->hasHeader($header)) {
                continue;
            }
            $url = $request->getHeaderLine($header);
            if (strpos($url, '/') === 0) {
                return array_pad(explode('?', $url, 2), 2, null);
            }
        }
        return null;
    }
}
