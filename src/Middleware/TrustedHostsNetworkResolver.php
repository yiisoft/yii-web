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

    private const DEFAULT_IP_HEADERS = [
        [self::IP_HEADER_TYPE_RFC7239, 'forward'],  // https://tools.ietf.org/html/rfc7239
        'x-forwarded-for',                          // common
    ];

    private const DEFAULT_HOST_HEADERS = [
        'x-forwarded-host', // common
    ];

    private const DEFAULT_URL_HEADERS = [
        'x-rewrite-url',    // Microsoft
    ];

    private const DEFAULT_PROTOCOL_HEADERS = [
        'x-forwarded-proto' => ['http' => 'http', 'https' => 'https'], // Common
        'front-end-https' => ['https' => 'on'], // Microsoft
    ];

    private const DEFAULT_TRUSTED_HEADERS = [
        // Common:
        'x-forwarded-for',
        'x-forwarded-host',
        'x-forwarded-proto',
        // RFC
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
    private $attributeIps = null;

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
        ?array $ipHeaders = null,
        ?array $protocolHeaders = null,
        ?array $hostHeaders = null,
        ?array $urlHeaders = null,
        ?array $trustedHeaders = null
    ) {
        $new = clone $this;
        $ipHeaders = $ipHeaders ?? self::DEFAULT_IP_HEADERS;
        foreach ($ipHeaders as $ipHeader) {
            if (is_string($ipHeader)) {
                continue;
            }
            if (!is_array($ipHeader)) {
                throw new \InvalidArgumentException('Type of ipHeader is not a string and not array');
            }
            if (count($ipHeader) !== 2) {
                throw new \InvalidArgumentException('The ipHeader array must have exactly 2 elements');
            }
            [$type, $header] = $ipHeader;
            if (!is_string($type)) {
                throw new \InvalidArgumentException('The type is not a string');
            }
            if (!is_string($header)) {
                throw new \InvalidArgumentException('The header is not a string');
            }
            switch ($type) {
                case self::IP_HEADER_TYPE_RFC7239:
                    continue 2;
                default:
                    throw new \InvalidArgumentException("Not supported IP header type: $type");
            }
        }
        $new->trustedHosts[] = [
            self::DATA_KEY_HOSTS => $hosts,
            self::DATA_KEY_IP_HEADERS => $ipHeaders,
            self::DATA_KEY_PROTOCOL_HEADERS => $this->prepareProtocolHeaders($protocolHeaders ?? self::DEFAULT_PROTOCOL_HEADERS),
            self::DATA_KEY_TRUSTED_HEADERS => $trustedHeaders ?? self::DEFAULT_TRUSTED_HEADERS,
            self::DATA_KEY_HOST_HEADERS => $hostHeaders ?? self::DEFAULT_HOST_HEADERS,
            self::DATA_KEY_URL_HEADERS => $urlHeaders ?? self::DEFAULT_URL_HEADERS,
        ];
        return $new;
    }

    private function checkIpHeaderTypes(array $ipHeaders): bool
    {
        $supportedTypes = [self::IP_HEADER_TYPE_RFC7239];
        foreach ($ipHeaders as $type => $ipHeader) {
            if (!is_string($ipHeader)) {
                continue;
            }
            if (!in_array($type, $supportedTypes)) {
                return false;
            }
        }
        return true;
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
        if ($attribute !== null && strlen($attribute) === 0) {
            throw new \RuntimeException('Attribute is cannot be an empty string');
        }
        $new = clone $this;
        $new->attributeIps = $attribute;
        return $new;
    }

    protected function prepareProtocolHeaders(array $protocolHeaders): array
    {
        $output = [];
        foreach ($protocolHeaders as $header => $protocolAndAcceptedValues) {
            $header = strtolower($header);
            if (is_callable($protocolAndAcceptedValues)) {
                $output[$header] = $protocolAndAcceptedValues;
                continue;
            }
            if (!is_array($protocolAndAcceptedValues)) {
                throw new \RuntimeException('Accepted values is not array nor callable');
            }
            if (count($protocolAndAcceptedValues) === 0) {
                throw new \RuntimeException('Accepted values cannot be an empty array');
            }
            $output[$header] = [];
            foreach ($protocolAndAcceptedValues as $protocol => $acceptedValues) {
                if (!is_string($protocol)) {
                    throw new \RuntimeException('The protocol must be type of string');
                }
                if (strlen($protocol) === 0) {
                    throw new \RuntimeException('The protocol cannot be an empty string');
                }
                $output[$header][$protocol] = array_map('strtolower', (array)$acceptedValues);
            }
        }
        return $output;
    }

    protected function removeHeaders(ServerRequestInterface $request, array $headers): ServerRequestInterface
    {
        foreach ($headers as $header) {
            $request = $request->withoutAttribute($header);
        }
        return $request;
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
                $ipData = $this->reverseObfuscate($ipData, $ipDataList);
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

        return $handler->handle($request->withAttribute('requestClientIp', end($ipDataList)['ip']));
    }

    protected function reverseObfuscate(array $ipData, array $ipDataList): array
    {
        return $ipData;
    }

    private function getIpList(RequestInterface $request, array $ipHeaders): array
    {
        foreach ($ipHeaders as $ipHeader) {
            $type = null;
            if (is_array($ipHeader)) {
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

    private function getForwardedElements(array $forwards): array
    {
        $list = [];
        foreach ($forwards as $forward) {
            $data = HeaderHelper::getParameters($forward);
            if (!isset($data['for'])) {
                // Invalid element, other items will be dropped.
                break;
            }
            $pattern = '/^(?<host>' . IpHelper::IPV4_PATTERN . '|_[^:]+|[[]' . IpHelper::IPV6_PATTERN . '[]])(?::(?<port>.+))?$/';
            if (preg_match($pattern, $data['for'], $matches) === 0) {
                // Invalid element, other items will be dropped.
                break;
            }
            $ipData = [];
            $host = $matches['host'];
            $obfuscatedHost = strpos($host, '_') === 0;
            if (!$obfuscatedHost) {
                // IPv4 & IPv6
                $ipData['ip'] = strpos($host, '[') === 0 ? trim($host /* IPv6 */, '[]') : $host;
            }
            $ipData['host'] = $host;
            if (isset($matches['port'])) {
                $port = $matches['port'];
                if (!$obfuscatedHost && (preg_match('/^\d{1,5}$/', $port) === 0 || intval($port) > 65535)) {
                    // Invalid port, other items will be dropped.
                    break;
                }
                $ipData['port'] = $obfuscatedHost ? $port : intval($port);
            }

            // copy other properties
            foreach (['proto' => 'protocol', 'host' => 'host', 'by' => 'by'] as $source => $destination) {
                if (isset($data[$source])) {
                    $ipData[$destination] = $data[$source];
                }
            }

            $list[] = $ipData;
        }
        return $list;
    }
}
