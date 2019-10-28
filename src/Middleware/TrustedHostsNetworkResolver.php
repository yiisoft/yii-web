<?php


namespace Yiisoft\Yii\Web\Middleware;


use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Validator\Rule\Ip;

class TrustedHostsNetworkResolver implements MiddlewareInterface
{

    private const DEFAULT_IP_HEADERS = [
        'X-Forwarded-For', // Common
    ];

    private const DEFAULT_PROTOCOL_HEADERS = [
        'X-Forwarded-Proto' => ['http' => 'http', 'https' => 'https'], // Common
        'Front-End-Https' => ['https' => 'on'], // Microsoft
    ];

    private const DEFAULT_TRUSTED_HEADERS = [
        // Common:
        'X-Forwarded-For',
        'X-Forwarded-Host',
        'X-Forwarded-Proto',

        // Microsoft:
        'Front-End-Https',
        'X-Rewrite-Url',
    ];

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
    public function withIpValidator(Ip $ipValidator) {
        $new = clone $this;
        $new->ipValidator = $ipValidator;
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
        ?array $trustedHeaders = null
    ) {
        $new = clone $this;
        $new->trustedHosts[] = [
            'hosts' => $hosts,
            'ipHeaders' => $ipHeaders ?? self::DEFAULT_IP_HEADERS,
            'protocolHeaders' => $this->prepareProtocolHeaders($protocolHeaders ?? self::DEFAULT_PROTOCOL_HEADERS),
            'trustedHeaders' => $trustedHeaders ?? self::DEFAULT_TRUSTED_HEADERS,
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
            throw new \RuntimeException('Attribute is cannot be an empty string!');
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
                throw new \RuntimeException('Accepted values is not array nor callable!');
            }
            if (count($protocolAndAcceptedValues) === 0) {
                throw new \RuntimeException('Accepted values cannot be an empty array!');
            }
            $output[$header] = [];
            foreach ($protocolAndAcceptedValues as $protocol => $acceptedValues) {
                if (!is_string($protocol)) {
                    throw new \RuntimeException('The protocol must be type of string!');
                }
                if (strlen($protocol) === 0) {
                    throw new \RuntimeException('The protocol cannot be an empty string!');
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
        $validator->setRanges($ranges);
        return $validator->validate($host)->isValid();
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
        $ips = [$actualHost];
        $trustedHostData = null;
        $trustedHeaders = [];
        $ipValidator = $this->ipValidator ?? new Ip();
        foreach ($this->trustedHosts as $data) {
            $trustedHeaders = array_merge($trustedHeaders, $data['trustedHeaders']);
            if ($trustedHostData !== null) {
                continue;
            }
            if (!$this->isValidHost($actualHost, $data['hosts'], $ipValidator)) {
                continue;
            }
            $trustedHostData = $data;
        }
        $untrustedHeaders = array_diff($trustedHeaders, $trustedHostData['trustedHeaders'] ?? []);
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

        $ipList = null;
        foreach ($trustedHostData['ipHeaders'] as $ipHeader) {
            if ($request->hasHeader($ipHeader)) {
                $ipList = $request->getHeader($ipHeader)[0];
                break;
            }
        }
        if ($ipList === null || strlen($ipList) === 0) {
            if ($this->attributeIps !== null) {
                $request = $request->withAttribute($this->attributeIps, []);
            }
            return $handler->handle($request);
        }

        $ipList = preg_split('/\s*,\s*/', trim($ipList), -1, PREG_SPLIT_NO_EMPTY);
        do {
            $ip = array_pop($ipList);
            if (!$this->isValidHost($ip, ['any'], $ipValidator)) {
                break;
            }
            $ips[] = $ip;
            if (!$this->isValidHost($ip, $trustedHostData['hosts'], $ipValidator)) {
                break;
            }
        } while (count($ipList) > 0);

        if ($this->attributeIps !== null) {
            $request = $request->withAttribute($this->attributeIps, $ips);
        }

        return $handler->handle($request->withAttribute('clientIp', end($ips)));
    }
}
