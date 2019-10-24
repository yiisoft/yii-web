<?php


namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\UploadedFile;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Yiisoft\Yii\Web\ServerRequestFactory;

class ServerRequestFactoryTest extends TestCase
{

    protected function getNewServerRequestFactory(): ServerRequestFactoryInterface
    {
        return new class implements ServerRequestFactoryInterface
        {

            public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
            {
                return new ServerRequest($method, $uri, [], null, '1.1', $serverParams);
            }
        };
    }

    protected function getNewUriFactory(): UriFactoryInterface
    {
        return new class implements UriFactoryInterface
        {

            public function createUri(string $uri = ''): UriInterface
            {
                return new Uri($uri);
            }
        };
    }

    protected function getNewUploadedFileFactory(): UploadedFileFactoryInterface
    {
        return new class implements UploadedFileFactoryInterface
        {
            public function createUploadedFile(
                StreamInterface $stream,
                int $size = null,
                int $error = \UPLOAD_ERR_OK,
                string $clientFilename = null,
                string $clientMediaType = null
            ): UploadedFileInterface {
                return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
            }
        };
    }

    protected function getNewStreamFactory(): StreamFactoryInterface
    {
        return new class implements StreamFactoryInterface
        {
            public function createStream(string $content = ''): StreamInterface
            {
                // TODO: Implement createStream() method.
            }

            public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
            {
                // TODO: Implement createStreamFromFile() method.
            }

            public function createStreamFromResource($resource): StreamInterface
            {
                // TODO: Implement createStreamFromResource() method.
            }
        };
    }

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
    public function testHostParsing(array $serverParams, ?string $expectedHost, ?int $expectedPort)
    {
        $serverRequestFactory = new ServerRequestFactory($this->getNewServerRequestFactory(), $this->getNewUriFactory(),
            $this->getNewUploadedFileFactory(), $this->getNewStreamFactory());
        if (!isset($serverParams['REQUEST_METHOD'])) {
            $serverParams['REQUEST_METHOD'] = 'GET';
        }
        $serverRequest = $serverRequestFactory->createFromParameters($serverParams);
        $this->assertSame($expectedHost, $serverRequest->getUri()->getHost());
        $this->assertSame($expectedPort, $serverRequest->getUri()->getPort());
    }
}
