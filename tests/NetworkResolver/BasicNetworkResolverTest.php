<?php


namespace Yiisoft\Yii\Web\Tests\NetworkResolver;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\Web\NetworkResolver\BasicNetworkResolver;

class BasicNetworkResolverTest extends TestCase
{

    public function simpleDataProvider()
    {
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
                ['x-forwarded-proto' => 'http'],
                ['x-forwarded-proto' => ['http' => 'http']],
                'http'
            ],
            'httpToHttpsWithCallback' => [
                'http',
                ['x-forwarded-proto' => 'test any-https **'],
                [
                    'x-forwarded-proto' => function (array $values, String $header, ServerRequestInterface $request) {
                        return stripos($values[0], 'https') !== false ? 'https' : 'http';
                    }
                ],
                'https',
            ]
        ];
    }

    /**
     * @dataProvider simpleDataProvider
     */
    public function testScheme(string $scheme, array $headers, ?array $protocolHeaders, string $expectedScheme)
    {
        $request = new ServerRequest('GET', '/', $headers);
        $uri = $request->getUri()->withScheme($scheme);
        $request = $request->withUri($uri);

        $nr = (new BasicNetworkResolver())->withServerRequest($request);
        if ($protocolHeaders !== null) {
            foreach ($protocolHeaders as $header => $values) {
                $nr = $nr->withNewProtocolHeader($header, $values);
            }
        }
        $this->assertSame($expectedScheme, $nr->getServerRequest()->getUri()->getScheme());
    }

    public function ipsDataProvider()
    {
        return [
            'ipv4' => ['9.9.9.9', '9.9.9.9'],
            'ipv6' => ['684D:1111:222:3333:4444:5555:6:77', '684D:1111:222:3333:4444:5555:6:77'],
        ];
    }

    /**
     * @dataProvider ipsDataProvider
     */
    public function testIps(string $remoteAddress, string $expectedIp)
    {
        $request = new ServerRequest('GET', '/', [], null, '1.1', [
            'REMOTE_ADDR' => $remoteAddress,
        ]);
        $nr = (new BasicNetworkResolver())->withServerRequest($request);
        $this->assertSame($expectedIp, $nr->getUserIp());
        $this->assertSame($expectedIp, $nr->getRemoteIp());
    }

}
