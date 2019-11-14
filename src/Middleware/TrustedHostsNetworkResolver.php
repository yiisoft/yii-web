<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Validator\Rule\Ip;

class TrustedHostsNetworkResolver implements MiddlewareInterface
{

    private const DEFAULT_IP_HEADERS = [
        'x-forwarded-for', // common
    ];

    private const DEFAULT_HOST_HEADERS = [
        'x-forwarded-host', // common
    ];

    private const DEFAULT_FORWARD_HEADERS = [
        'forward',  // https://tools.ietf.org/html/rfc7239#section-4
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
    private const DATA_KEY_FORWARD_HEADERS = 'forwardHeaders';
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
        ?array $forwardHeaders = null,
        ?array $trustedHeaders = null
    ) {
        $new = clone $this;
        $new->trustedHosts[] = [
            self::DATA_KEY_HOSTS => $hosts,
            self::DATA_KEY_IP_HEADERS => $ipHeaders ?? self::DEFAULT_IP_HEADERS,
            self::DATA_KEY_PROTOCOL_HEADERS => $this->prepareProtocolHeaders($protocolHeaders ?? self::DEFAULT_PROTOCOL_HEADERS),
            self::DATA_KEY_TRUSTED_HEADERS => $trustedHeaders ?? self::DEFAULT_TRUSTED_HEADERS,
            self::DATA_KEY_HOST_HEADERS => $hostHeaders ?? self::DEFAULT_HOST_HEADERS,
            self::DATA_KEY_URL_HEADERS => $urlHeaders ?? self::DEFAULT_URL_HEADERS,
            self::DATA_KEY_FORWARD_HEADERS => $forwardHeaders ?? self::DEFAULT_FORWARD_HEADERS,
        ];
        return $new;
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
        $ips = [['for' => $actualHost]];
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

        $ipList = $this->getIpList($request, $trustedHostData[self::DATA_KEY_IP_HEADERS]);
        if (count($ipList) === 0) {
            if ($this->attributeIps !== null) {
                $request = $request->withAttribute($this->attributeIps, $ips);
            }
            return $handler->handle($request);
        }

        do {
            $ip = array_pop($ipList);
            if (!$this->isValidHost($ip, ['any'], $ipValidator)) {
                break;
            }
            $ips[] = ['for' => $ip];
            if (!$this->isValidHost($ip, $trustedHostData[self::DATA_KEY_HOSTS], $ipValidator)) {
                break;
            }
        } while (count($ipList) > 0);

        if ($this->attributeIps !== null) {
            $request = $request->withAttribute($this->attributeIps, $ips);
        }

        return $handler->handle($request->withAttribute('requestClientIp', end($ips)['for']));
    }

    private function getIpList(RequestInterface $request, array $ipHeaders): array
    {
        foreach ($ipHeaders as $ipHeader) {
            if ($request->hasHeader($ipHeader)) {
                return $request->getHeader($ipHeader);
            }
        }
        return [];
    }
}
