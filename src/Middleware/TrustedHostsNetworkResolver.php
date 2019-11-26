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
     * @return static
     */
    public function withNotTrustedBranch(?MiddlewareInterface $middleware)
    {
        $new = clone $this;
        $new->notTrustedBranch = $middleware;
        return $new;
    }

    /**
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
     * @return static
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

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
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
        [$type, $ipList] = $this->getIpList($request, $trustedHostData[self::DATA_KEY_IP_HEADERS]);
        $ipList = array_reverse($ipList);       // the first item should be the closest to the server
        if ($type === null) {
            $ipList = $this->getFormattedIpList($ipList);
        } elseif ($type === self::IP_HEADER_TYPE_RFC7239) {
            $ipList = $this->getForwardedElements($ipList);
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
        if (isset($ipData['httpHost'])) {
            $uri = $uri->withHost($ipData['httpHost']);
        } else {
            // find host from headers
            $host = $this->getHttpHost($request, $trustedHostData[self::DATA_KEY_HOST_HEADERS]);
            if ($host !== null) {
                $uri = $uri->withHost($host);
            }
        }
        if (isset($ipData['protocol'])) {
            $uri = $uri->withScheme($ipData['protocol']);
        } else {
            // find scheme from headers
            $scheme = $this->getScheme($request, $trustedHostData[self::DATA_KEY_PROTOCOL_HEADERS]);
            if ($scheme !== null) {
                $uri = $uri->withScheme($scheme);
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
                return [$type, $request->getHeader($ipHeader)];
            }
        }
        return [null, []];
    }

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
     * @link https://tools.ietf.org/html/rfc7239
     */
    private function getForwardedElements(array $forwards): array
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

            $list[] = $ipData;
        }
        return $list;
    }

    private function getHttpHost(RequestInterface $request, array $hostHeaders): ?string
    {
        foreach ($hostHeaders as $header) {
            if (!$request->hasHeader($header)) {
                continue;
            }
            $host = $request->getHeaderLine($header);
            if (filter_var($host, FILTER_VALIDATE_DOMAIN) !== false) {
                return $host;
            }
        }
        return null;
    }

    private function getScheme(RequestInterface $request, array $protocolHeaders): ?string
    {
        foreach ($protocolHeaders as $header => $ref) {
            if (!$request->hasHeader($header)) {
                continue;
            }
            $value = strtolower($request->getHeaderLine($header));
            foreach ($ref as $protocol => $acceptedValues) {
                if (\in_array($value, $acceptedValues, true)) {
                    return $protocol;
                }
            }
        }
        return null;
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
