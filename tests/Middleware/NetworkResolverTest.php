<?php


namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Middleware\NetworkResolver;
use Yiisoft\Yii\Web\NetworkResolver\BasicNetworkResolver;

class NetworkResolverTest extends TestCase
{

    public function simpleDataProvider()
    {
        // @TODO Only relevant tests for middleware
        return [
            'httpNotModify' => ['http', [], null, 'http'],
            'httpsNotModify' => ['https', [], null, 'https'],
            'httpNotMatchedProtocolHeader' => [
                'http',
                ['x-forwarded-proto' => ['https']],
                ['test' => ['https' => 'https']],
                'http'
            ],
            'httpNotMatchedProtocolHeaderValue' => [
                'http',
                ['x-forwarded-proto' => ['https']],
                ['x-forwarded-proto' => ['https' => 'test']],
                'http'
            ],
            'httpToHttps' => [
                'http',
                ['x-forwarded-proto' => ['https']],
                ['x-forwarded-proto' => ['https' => 'https']],
                'https'
            ],
            'httpToHttpsUpperCase' => [
                'http',
                ['x-forwarded-proto' => ['https']],
                ['x-forwarded-proto' => ['https' => 'HTTPS']],
                'https'
            ],
            'httpToHttpsMultiValue' => [
                'http',
                ['x-forwarded-proto' => ['https']],
                ['x-forwarded-proto' => ['https' => ['on', 's', 'https']]],
                'https'
            ],
            'httpsToHttp' => [
                'https',
                ['x-forwarded-proto' => ['http']],
                ['x-forwarded-proto' => ['http' => 'http']],
                'http'
            ],
            // @TODO callback test
        ];
    }

    /**
     * @dataProvider simpleDataProvider
     */
    public function testSimple(string $scheme, array $headers, ?array $protocolHeaders, string $expectedScheme)
    {
        $request = new ServerRequest('GET', '/', $headers);
        $uri = $request->getUri()->withScheme($scheme);
        $request = $request->withUri($uri);

        $requestHandler = new class implements RequestHandlerInterface
        {
            public $request;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;
                return new Response(200);
            }
        };

        $nr = new BasicNetworkResolver();
        if ($protocolHeaders !== null) {
            foreach ($protocolHeaders as $header => $values) {
                $nr = $nr->withNewProtocolHeader($header, $values);
            }
        }
        $middleware = new NetworkResolver($nr);
        $middleware->process($request, $requestHandler);
        $resultRequest = $requestHandler->request;
        /* @var $resultRequest ServerRequestInterface */
        $this->assertSame($expectedScheme, $resultRequest->getUri()->getScheme());
    }

}
