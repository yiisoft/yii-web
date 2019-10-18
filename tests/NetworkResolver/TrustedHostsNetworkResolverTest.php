<?php


namespace Yiisoft\Yii\Web\Tests\NetworkResolver;


use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\NetworkResolver\TrustedHostsNetworkResolver;

class TrustedHostsNetworkResolverTest extends TestCase
{

    public function ipsDataProvider()
    {
        return [
            ['127.0.0.1', ['x-forwarded-for' => ['8.8.8.8, 9.9.9.9']], ['127.0.0.1'], null, '9.9.9.9'],
        ];
    }

    /**
     * @dataProvider ipsDataProvider
     */
    public function testIps(
        string $remoteAddress,
        array $headers,
        array $trustedHosts,
        ?array $ipHeaders,
        $expectedUserIp
    ) {
        $request = new ServerRequest('GET', '/', $headers, null, '1.1', [
            'REMOTE_ADDR' => $remoteAddress,
        ]);
        $nr = (new TrustedHostsNetworkResolver())->withServerRequest($request)
            ->withNewTrustedHosts($trustedHosts, $ipHeaders);
        $this->assertSame($expectedUserIp, $nr->getUserIp());
    }

}
