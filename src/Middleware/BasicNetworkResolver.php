<?php


namespace Yiisoft\Yii\Web\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BasicNetworkResolver implements MiddlewareInterface
{
    private const DEFAULT_PROTOCOL_AND_ACCEPTABLE_VALUES = [
        'http' => ['http'],
        'https' => ['https', 'on'],
    ];

    private $protocolHeaders = [];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $newScheme = null;
        foreach ($this->protocolHeaders as $header => $data) {
            if (!$request->hasHeader($header)) {
                continue;
            }
            $headerValues = $request->getHeader($header);
            if (is_callable($data)) {
                $newScheme = call_user_func($data, $headerValues, $header, $request);
                if ($newScheme === null) {
                    continue;
                } elseif (!is_string($newScheme)) {
                    throw new \RuntimeException('The scheme is neither string nor null!');
                } elseif (strlen($newScheme) === 0) {
                    throw new \RuntimeException('The scheme cannot be an empty string!');
                }
                break;
            }
            $headerValue = strtolower($headerValues[0]);
            foreach ($data as $protocol => $acceptedValues) {
                if (!in_array($headerValue, $acceptedValues)) {
                    continue;
                }
                $newScheme = $protocol;
                break 2;
            }
        }
        $uri = $request->getUri();
        if ($newScheme !== null && $newScheme !== $uri->getScheme()) {
            $request = $request->withUri($uri->withScheme($newScheme));
        }
        return $handler->handle($request);
    }

    /**
     * @TODO: documentation
     * @param callable|array|null $protocolAndAcceptedValues
     * @return static
     */
    public function withProtocolHeader(string $header, $protocolAndAcceptedValues = null)
    {
        $new = clone $this;
        $header = strtolower($header);
        if ($protocolAndAcceptedValues === null) {
            $new->protocolHeaders[$header] = self::DEFAULT_PROTOCOL_AND_ACCEPTABLE_VALUES;
        } elseif (is_callable($protocolAndAcceptedValues)) {
            $new->protocolHeaders[$header] = $protocolAndAcceptedValues;
        } elseif (!is_array($protocolAndAcceptedValues)) {
            throw new \RuntimeException('$protocolAndAcceptedValues is not array nor callable!');
        } elseif (is_array($protocolAndAcceptedValues) && count($protocolAndAcceptedValues) === 0) {
            throw new \RuntimeException('$protocolAndAcceptedValues cannot be an empty array!');
        } else {
            $new->protocolHeaders[$header] = [];
            foreach ($protocolAndAcceptedValues as $protocol => $acceptedValues) {
                if (!is_string($protocol)) {
                    throw new \RuntimeException('The protocol must be type of string!');
                }
                $new->protocolHeaders[$header][$protocol] = array_map('strtolower', (array)$acceptedValues);
            }
        }
        return $new;
    }

    /**
     * @return static
     */
    public function withoutProtocolHeader(string $header)
    {
        $new = clone $this;
        unset($new->protocolHeaders[strtolower($header)]);
        return $new;
    }

    /**
     * @return static
     */
    public function withoutProtocolHeaders(?array $headers = null)
    {
        $new = clone $this;
        if ($headers === null) {
            $new->protocolHeaders = [];
        } else {
            foreach ($headers as $header) {
                $new = $new->withoutProtocolHeader($header);
            }
        }
        return $new;
    }
}
