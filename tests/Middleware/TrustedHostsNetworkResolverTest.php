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

    public function notTrustedDataProvider(): array
    {
        return [
            'none' => [
                [],
                ['REMOTE_ADDR' => '127.0.0.1'],
                [],
            ],
            'level1' => [
                ['x-forwarded-for' => '9.9.9.9, 5.5.5.5, 2.2.2.2'],
                ['REMOTE_ADDR' => '127.0.0.1'],
                [['hosts' => ['8.8.8.8']]],
            ],
        ];
    }

    /**
     * @dataProvider notTrustedDataProvider
     */
    public function testNotTrusted(array $headers, array $serverParams, array $trustedHosts)
    {
        $request = $this->newRequestWithSchemaAndHeaders('http', $headers, $serverParams);
        $requestHandler = new MockRequestHandler();

        $middleware = new TrustedHostsNetworkResolver(new Psr17Factory());
        foreach ($trustedHosts as $data) {
            $middleware = $middleware->withAddedTrustedHosts(
                $data['hosts'],
                $data['ipHeaders'] ?? null,
                $data['protocolHeaders'] ?? null,
                null,
                null,
                null,
                $data['trustedHeaders'] ?? null);
        }
        $response = $middleware->process($request, $requestHandler);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(412, $response->getStatusCode());
    }

    public function testNotTrustedMiddleware()
    {
        $request = $this->newRequestWithSchemaAndHeaders('http', [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $requestHandler = new MockRequestHandler();

        $middleware = new TrustedHostsNetworkResolver(new Psr17Factory());
        $middleware = $middleware->withNotTrustedBranch(new class() implements MiddlewareInterface
        {

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = (new Psr17Factory())->createResponse(200);
                $response->getBody()->write('Another branch.');
                return $response;
            }
        });
        $response = $middleware->process($request, $requestHandler);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }
}
