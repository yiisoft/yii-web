<?php


namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\ServerRequestFactory;

class ServerRequestFactoryTest extends TestCase
{
    public function hostParsingDataProvider(): array
    {
        return [
            'host' => [
                ['HTTP_HOST' => 'test'],
                'test',
                null,
            ],
            'hostWithPort' => [
                ['HTTP_HOST' => 'test:88'],
                'test',
                88,
            ],
            'ipv4' => [
                ['HTTP_HOST' => '127.0.0.1'],
                '127.0.0.1',
                null,
            ],
            'ipv4WithPort' => [
                ['HTTP_HOST' => '127.0.0.1:443'],
                '127.0.0.1',
                443,
            ],
            'ipv6' => [
                ['HTTP_HOST' => '[::1]'],
                '[::1]',
                null,
            ],
            'ipv6WithPort' => [
                ['HTTP_HOST' => '[::1]:443'],
                '[::1]',
                443,
            ],
            'serverName' => [
                ['SERVER_NAME' => 'test'],
                'test',
                null,
            ],
            'hostAndServerName' => [
                ['SERVER_NAME' => 'override', 'HTTP_HOST' => 'test'],
                'test',
                null,
            ],
            'none' => [
                [],
                '',
                null,
            ],
        ];
    }

    /**
     * @dataProvider hostParsingDataProvider
     */
    public function testHostParsing(array $serverParams, ?string $expectedHost, ?int $expectedPort): void
    {
        $factory = new Psr17Factory();

        $serverRequestFactory = new ServerRequestFactory(
            $factory,
            $factory,
            $factory,
            $factory
        );
        if (!isset($serverParams['REQUEST_METHOD'])) {
            $serverParams['REQUEST_METHOD'] = 'GET';
        }
        $serverRequest = $serverRequestFactory->createFromParameters($serverParams);
        $this->assertSame($expectedHost, $serverRequest->getUri()->getHost());
        $this->assertSame($expectedPort, $serverRequest->getUri()->getPort());
    }
}
