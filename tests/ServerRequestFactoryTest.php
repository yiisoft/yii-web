<?php


namespace Yiisoft\Yii\Web\Tests;

use RuntimeException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\ServerRequestFactory;

class ServerRequestFactoryTest extends TestCase
{
    /**
     * @dataProvider hostParsingDataProvider
     */
    public function testHostParsing(array $serverParams, array $expectParams): void
    {
        $serverRequest = $this->getServerRequestFactory()->createFromParameters($serverParams);
        $this->assertSame($expectParams['host'], $serverRequest->getUri()->getHost());
        $this->assertSame($expectParams['port'], $serverRequest->getUri()->getPort());
        $this->assertSame($expectParams['method'], $serverRequest->getMethod());
        $this->assertSame($expectParams['protocol'], $serverRequest->getProtocolVersion());
        $this->assertSame($expectParams['scheme'], $serverRequest->getUri()->getScheme());
        $this->assertSame($expectParams['path'], $serverRequest->getUri()->getPath());
        $this->assertSame($expectParams['query'], $serverRequest->getUri()->getQuery());
    }

    /**
     * @dataProvider hostParsingDataProvider
     * @backupGlobals enabled
     */
    public function testCreateFromGlobals(array $serverParams, array $expectParams): void
    {
        $_SERVER = $serverParams;
        $serverRequest = $this->getServerRequestFactory()->createFromGlobals();
        $this->assertSame($expectParams['host'], $serverRequest->getUri()->getHost());
        $this->assertSame($expectParams['port'], $serverRequest->getUri()->getPort());
        $this->assertSame($expectParams['method'], $serverRequest->getMethod());
        $this->assertSame($expectParams['protocol'], $serverRequest->getProtocolVersion());
        $this->assertSame($expectParams['scheme'], $serverRequest->getUri()->getScheme());
        $this->assertSame($expectParams['path'], $serverRequest->getUri()->getPath());
        $this->assertSame($expectParams['query'], $serverRequest->getUri()->getQuery());
    }

    public function testInvalidMethodException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to determine HTTP request method.');
        $this->getServerRequestFactory()->createFromParameters([]);
    }

    public function hostParsingDataProvider(): array
    {
        return [
            'host' => [
                [
                    'HTTP_HOST' => 'test',
                    'REQUEST_METHOD' => 'GET',
                ],
                [
                    'method' => 'GET',
                    'host' => 'test',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ]
            ],
            'hostWithPort' => [
                [
                    'HTTP_HOST' => 'test:88',
                    'REQUEST_METHOD' => 'GET',
                ],
                [
                    'method' => 'GET',
                    'host' => 'test',
                    'port' => 88,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',

                ]
            ],
            'ipv4' => [
                [
                    'HTTP_HOST' => '127.0.0.1',
                    'REQUEST_METHOD' => 'GET',
                    'HTTPS' => true
                ],
                [
                    'method' => 'GET',
                    'host' => '127.0.0.1',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'https',
                    'path' => '',
                    'query' => '',
                ]
            ],
            'ipv4WithPort' => [
                [
                    'HTTP_HOST' => '127.0.0.1:443',
                    'REQUEST_METHOD' => 'GET'
                ],
                [
                    'method' => 'GET',
                    'host' => '127.0.0.1',
                    'port' => 443,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ]
            ],
            'ipv6' => [
                [
                    'HTTP_HOST' => '[::1]',
                    'REQUEST_METHOD' => 'GET'
                ],
                [
                    'method' => 'GET',
                    'host' => '[::1]',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ]
            ],
            'ipv6WithPort' => [
                [
                    'HTTP_HOST' => '[::1]:443',
                    'REQUEST_METHOD' => 'GET'
                ],
                [
                    'method' => 'GET',
                    'host' => '[::1]',
                    'port' => 443,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ]
            ],
            'serverName' => [
                [
                    'SERVER_NAME' => 'test',
                    'REQUEST_METHOD' => 'GET'
                ],
                [
                    'method' => 'GET',
                    'host' => 'test',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ]
            ],
            'hostAndServerName' => [
                [
                    'SERVER_NAME' => 'override',
                    'HTTP_HOST' => 'test',
                    'REQUEST_METHOD' => 'GET',
                    'SERVER_PORT' => 81
                ],
                [
                    'method' => 'GET',
                    'host' => 'test',
                    'port' => 81,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ]
            ],
            'none' => [
                [
                    'REQUEST_METHOD' => 'GET'
                ],
                [
                    'method' => 'GET',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ]
            ],
            'path' => [
                [
                    'REQUEST_METHOD' => 'GET',
                    'REQUEST_URI' => '/path/to/folder?param=1'
                ],
                [
                    'method' => 'GET',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '/path/to/folder',
                    'query' => '',
                ]
            ],
            'query' => [
                [
                    'REQUEST_METHOD' => 'GET',
                    'QUERY_STRING' => 'path/to/folder?param=1'
                ],
                [
                    'method' => 'GET',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => 'path/to/folder?param=1',
                ]
            ],
            'protocol' => [
                [
                    'REQUEST_METHOD' => 'GET',
                    'SERVER_PROTOCOL' => 'HTTP/1.0'
                ],
                [
                    'method' => 'GET',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.0',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ]
            ],
            'post' => [
                [
                    'REQUEST_METHOD' => 'POST',
                ],
                [
                    'method' => 'POST',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ]
            ],
            'delete' => [
                [
                    'REQUEST_METHOD' => 'DELETE',
                ],
                [
                    'method' => 'DELETE',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ]
            ],
            'put' => [
                [
                    'REQUEST_METHOD' => 'PUT',
                ],
                [
                    'method' => 'PUT',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ]
            ],
            'https' => [
                [
                    'REQUEST_METHOD' => 'PUT',
                    'HTTPS' => 'on'
                ],
                [
                    'method' => 'PUT',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'https',
                    'path' => '',
                    'query' => '',
                ]
            ],
        ];
    }

    private function getServerRequestFactory(): ServerRequestFactory
    {
        $factory = new Psr17Factory();
        return new ServerRequestFactory($factory, $factory, $factory, $factory);
    }
}
