<?php


namespace Yiisoft\Yii\Web\NetworkResolver;


use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Validator\Rule\Ip;

class TrustedHostsNetworkResolver implements NetworkResolverInterface
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
     * @var ServerRequestInterface|null
     */
    private $baseServerRequest;

    /**
     * @var int
     */
    private $remoteIpIndex = 0;

    /**
     * @var array
     */
    private $cacheIpList = [];
    private $cacheIsTrusted = false;
    /**
     * @var ServerRequestInterface|null
     */
    private $cacheServerRequest;

    /**
     * @return static
     */
    public function withNewTrustedHosts(
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

    protected function prepareProtocolHeaders(array $protocolHeaders): array
    {
        $output = [];
        foreach ($protocolHeaders as $header => $protocolAndAcceptedValues) {
            if (is_callable($protocolAndAcceptedValues)) {
                $output[$header] = $protocolAndAcceptedValues;
            } elseif (!is_array($protocolAndAcceptedValues)) {
                throw new \RuntimeException('$protocolAndAcceptedValues is not array nor callable!');
            } elseif (is_array($protocolAndAcceptedValues) && count($protocolAndAcceptedValues) === 0) {
                throw new \RuntimeException('$protocolAndAcceptedValues cannot be an empty array!');
            } else {
                $output[$header] = [];
                foreach ($protocolAndAcceptedValues as $protocol => $acceptedValues) {
                    if (!is_string($protocol)) {
                        throw new \RuntimeException('The protocol must be type of string!');
                    }
                    $output[$header][$protocol] = array_map('strtolower', (array)$acceptedValues);
                }
            }
        }
        return $output;
    }

    /**
     * @return static
     */
    public function withServerRequest(ServerRequestInterface $serverRequest)
    {
        $new = clone $this;
        $new->baseServerRequest = $serverRequest;
        return $new;
    }

    public function getRemoteIp(): string
    {
        $this->getServerRequest();
        return $this->cacheIpList[0];
    }

    public function getUserIp(): string
    {
        $this->getServerRequest();
        return end($this->cacheIpList);
    }

    /**
     * Security of user's connection
     */
    public function isSecureConnection(): bool
    {
        return $this->getServerRequest()->getUri()->getScheme() === 'https';
    }

    public function __clone()
    {
        $this->cacheIpList = [];
        $this->cacheIsTrusted = false;
        $this->cacheServerRequest = null;
    }

    protected function getBaseServerRequest(bool $throwIfNull = true): ?ServerRequestInterface
    {
        if ($this->baseServerRequest === null && $throwIfNull) {
            throw new \RuntimeException('The server request object is not set!');
        }
        return $this->baseServerRequest;
    }

    public function getServerRequest(): ServerRequestInterface
    {
        if ($this->cacheServerRequest !== null) {
            return $this->cacheServerRequest;
        }

        $request = $this->getBaseServerRequest();
        $actualHost = $request->getServerParams()['REMOTE_ADDR'];
        $this->cacheIpList = [$actualHost];
        $trustedHostData = null;
        $trustedHeadersMerge = [];
        foreach ($this->trustedHosts as $data) {
            $trustedHeadersMerge = array_merge($trustedHeadersMerge, $data['trustedHeaders']);
            if ($trustedHostData !== null) {
                continue;
            } elseif (!$this->isValidHost($actualHost, $data['hosts'])) {
                continue;
            }
            $trustedHostData = $data;
        }
        if ($trustedHostData === null) {
            // No trusted host at all.
            return $this->cacheServerRequest = $this->removeHeaders($request, $trustedHeadersMerge);
        }

        $request = $this->removeHeaders($request, array_diff($trustedHeadersMerge, $trustedHostData['trustedHeaders']));

        $ipList = null;
        foreach ($trustedHostData['ipHeaders'] as $ipHeader) {
            if ($request->hasHeader($ipHeader)) {
                $ipList = $request->getHeader($ipHeader)[0];
                break;
            }
        }

        if ($ipList !== null) {
            $ips = preg_split('/\s*,\s*/', trim($ipList), -1, PREG_SPLIT_NO_EMPTY);
            while (count($ips)) {
                $ip = array_pop($ips);
                if($this->isValidHost($ip, ['any'])) {
                    $this->cacheIpList[] = $ip;
                }
                if (!$this->isValidHost($ip, $trustedHostData['hosts'])) {
                    break;
                }
            }
        }

        return $this->cacheServerRequest = $request;
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
     *
     * @param string   $host
     * @param string[] $ranges
     */
    protected function isValidHost(string $host, array $ranges): bool
    {
// @TODO Ip validator not working
//        if($ranges == ['any']) {
//            return true;
//        }
//        return $host == $ranges[0];
        $validator = new Ip();
        $validator->setRanges($ranges);
        return $validator->validateValue($host)->isValid();
    }
}
