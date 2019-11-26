<?php

namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Middleware\TrustedHostsNetworkResolver;
use Yiisoft\Yii\Web\Tests\Middleware\Mock\MockRequestHandler;

class TrustedHostsNetworkResolverTest extends TestCase
{
    protected function newRequestWithSchemaAndHeaders(
        string $scheme = 'http',
        array $headers = [],
        array $serverParams = []
    ): ServerRequestInterface {
        $request = new ServerRequest('GET', '/', $headers, null, '1.1', $serverParams);
        $uri = $request->getUri()->withScheme($scheme);
        return $request->withUri($uri);
    }

    public function trustedDataProvider(): array
    {
        return [
            'xForwardLevel1' => [
                ['x-forwarded-for' => ['9.9.9.9', '5.5.5.5', '2.2.2.2']],
                ['REMOTE_ADDR' => '127.0.0.1'],
                [
                    ['hosts' => ['8.8.8.8', '127.0.0.1'], 'ipHeaders' => ['x-forwarded-for']]
                ],
                '2.2.2.2',
            ],
            'xForwardLevel2' => [
                ['x-forwarded-for' => ['9.9.9.9', '5.5.5.5', '2.2.2.2']],
                ['REMOTE_ADDR' => '127.0.0.1'],
                [
                    ['hosts' => ['8.8.8.8', '127.0.0.1', '2.2.2.2'], 'ipHeaders' => ['x-forwarded-for']],
                ],
                '5.5.5.5',
            ],
            'forwardLevel1' => [
                ['forward' => ['for=9.9.9.9', 'for=5.5.5.5', 'for=2.2.2.2']],
                ['REMOTE_ADDR' => '127.0.0.1'],
                [
                    [
                        'hosts' => ['8.8.8.8', '127.0.0.1'],
                        'ipHeaders' => [[TrustedHostsNetworkResolver::IP_HEADER_TYPE_RFC7239, 'forward']],
                    ]
                ],
                '2.2.2.2',
            ],
            'forwardLevel2' => [
                ['forward' => ['for=9.9.9.9', 'for=5.5.5.5', 'for=2.2.2.2']],
                ['REMOTE_ADDR' => '127.0.0.1'],
                [
                    [
                        'hosts' => ['8.8.8.8', '127.0.0.1', '2.2.2.2'],
                        'ipHeaders' => [[TrustedHostsNetworkResolver::IP_HEADER_TYPE_RFC7239, 'forward']],
                    ],

                ],
                '5.5.5.5',
            ],
            'forwardLevel2HostAndProtocol' => [
                ['forward' => ['for=9.9.9.9', 'proto=https;for=5.5.5.5;host=test', 'for=2.2.2.2']],
                ['REMOTE_ADDR' => '127.0.0.1'],
                [
                    [
                        'hosts' => ['8.8.8.8', '127.0.0.1', '2.2.2.2'],
                        'ipHeaders' => [[TrustedHostsNetworkResolver::IP_HEADER_TYPE_RFC7239, 'forward']],
                    ],
                ],
                '5.5.5.5',
                'test',
                'https',
            ],
            'forwardLevel2HostAndProtocolAndUrl' => [
                [
                    'forward' => ['for=9.9.9.9', 'proto=https;for=5.5.5.5;host=test', 'for=2.2.2.2'],
                    'x-rewrite-url' => ['/test?test=test'],
                ],
                ['REMOTE_ADDR' => '127.0.0.1'],
                [
                    [
                        'hosts' => ['8.8.8.8', '127.0.0.1', '2.2.2.2'],
                        'ipHeaders' => [[TrustedHostsNetworkResolver::IP_HEADER_TYPE_RFC7239, 'forward']],
                        'urlHeaders' => ['x-rewrite-url'],
                    ],
                ],
                '5.5.5.5',
                'test',
                'https',
                '/test',
                'test=test',
            ],
        ];
    }

    /**
     * @dataProvider trustedDataProvider
     */
    public function testTrusted(
        array $headers,
        array $serverParams,
        array $trustedHosts,
        string $expectedClientIp,
        ?string $expectedHttpHost = null,
        string $expectedHttpScheme = 'http',
        string $expectedPath = '/',
        string $expectedQuery = ''
    ): void {
        $request = $this->newRequestWithSchemaAndHeaders('http', $headers, $serverParams);
        $requestHandler = new MockRequestHandler();

        $middleware = new TrustedHostsNetworkResolver(new Psr17Factory());
        foreach ($trustedHosts as $data) {
            $middleware = $middleware->withAddedTrustedHosts(
                $data['hosts'],
                $data['ipHeaders'] ?? [],
                $data['protocolHeaders'] ?? [],
                $data['hostHeaders'] ?? [],
                $data['urlHeaders'] ?? [],
                $data['trustedHeaders'] ?? null);
        }
        $response = $middleware->process($request, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedClientIp, $requestHandler->processedRequest->getAttribute('requestClientIp'));
        if ($expectedHttpHost !== null) {
            $this->assertSame($expectedHttpHost, $requestHandler->processedRequest->getUri()->getHost());
        }
        $this->assertSame($expectedHttpScheme, $requestHandler->processedRequest->getUri()->getScheme());
        $this->assertSame($expectedPath, $requestHandler->processedRequest->getUri()->getPath());
        $this->assertSame($expectedQuery, $requestHandler->processedRequest->getUri()->getQuery());
    }

    public function notTrustedDataProvider(): array
    {
        return [
            'none' => [
                [],
                ['REMOTE_ADDR' => '127.0.0.1'],
                [],
            ],
            'x-forwarded-for' => [
                ['x-forwarded-for' => ['9.9.9.9', '5.5.5.5', '2.2.2.2']],
                ['REMOTE_ADDR' => '127.0.0.1'],
                [['hosts' => ['8.8.8.8'], 'ipHeaders' => ['x-forwarded-for']]],
            ],
            'forward' => [
                ['x-forwarded-for' => ['for=9.9.9.9', 'for=5.5.5.5', 'for=2.2.2.2']],
                ['REMOTE_ADDR' => '127.0.0.1'],
                [['hosts' => ['8.8.8.8'], 'ipHeaders' => ['x-forwarded-for']]],
            ],
        ];
    }

    /**
     * @dataProvider notTrustedDataProvider
     */
    public function testNotTrusted(array $headers, array $serverParams, array $trustedHosts): void
    {
        $request = $this->newRequestWithSchemaAndHeaders('http', $headers, $serverParams);
        $requestHandler = new MockRequestHandler();

        $middleware = new TrustedHostsNetworkResolver(new Psr17Factory());
        foreach ($trustedHosts as $data) {
            $middleware = $middleware->withAddedTrustedHosts(
                $data['hosts'],
                $data['ipHeaders'] ?? [],
                $data['protocolHeaders'] ?? [],
                [],
                [],
                $data['trustedHeaders'] ?? []);
        }
        $response = $middleware->process($request, $requestHandler);
        $this->assertSame(412, $response->getStatusCode());
    }

    public function testNotTrustedMiddleware(): void
    {
        $request = $this->newRequestWithSchemaAndHeaders('http', [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $requestHandler = new MockRequestHandler();

        $middleware = new TrustedHostsNetworkResolver(new Psr17Factory());
        $content = 'Another branch.';
        $middleware = $middleware->withNotTrustedBranch(new class($content) implements MiddlewareInterface
        {
            private $content;

            public function __construct(string $content)
            {
                $this->content = $content;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = (new Psr17Factory())->createResponse(403);
                $response->getBody()->write($this->content);
                return $response;
            }
        });
        $response = $middleware->process($request, $requestHandler);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(403, $response->getStatusCode());
        $body = $response->getBody();
        $body->rewind();
        $this->assertSame($content, $body->getContents());
    }

    public function addedTrustedHostsInvalidParameterDataProvider(): array
    {
        return [
            'hostsEmpty' => ['hosts' => []],
            'hostsEmptyString' => ['hosts' => ['']],
            'hostsNumeric' => ['hosts' => [888]],
            'hostsSpaces' => ['hosts' => ['    ']],
            'hostsNotDomain' => ['host' => ['-apple']],
            'urlHeadersEmpty' => ['urlHeaders' => ['']],
            'urlHeadersNumeric' => ['urlHeaders' => [888]],
            'urlHeadersSpaces' => ['urlHeaders' => ['   ']],
            'trustedHeadersEmpty' => ['trustedHeaders' => ['']],
            'trustedHeadersNumeric' => ['trustedHeaders' => [888]],
            'trustedHeadersSpaces' => ['trustedHeaders' => ['   ']],
            'protocolHeadersNumeric' => ['protocolHeaders' => ['http' => 888]],
            'ipHeadersEmptyString' => ['ipHeaders' => [' ']],
            'ipHeadersNumeric' => ['ipHeaders' => [888]],
            'ipHeadersInvalidType' => ['ipHeaders' => [['---', 'aaa']]],
            'ipHeadersInvalidTypeValue' => [
                'ipHeaders' => [
                    [
                        TrustedHostsNetworkResolver::IP_HEADER_TYPE_RFC7239,
                        888
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider addedTrustedHostsInvalidParameterDataProvider
     */
    public function testAddedTrustedHostsInvalidParameter(array $data): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new TrustedHostsNetworkResolver(new Psr17Factory()))
            ->withAddedTrustedHosts($data['hosts'] ?? [],
                $data['ipHeaders'] ?? [],
                $data['protocolHeaders'] ?? [],
                $data['hostHeaders'] ?? [],
                $data['urlHeaders'] ?? [],
                $data['trustedHeaders'] ?? null
            );
    }
}
