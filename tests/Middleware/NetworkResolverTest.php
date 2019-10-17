<?php


namespace Yiisoft\Yii\Web\Tests\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Middleware\NetworkResolver;
use Yiisoft\Yii\Web\NetworkResolver\NetworkResolverInterface;

class NetworkResolverTest extends TestCase
{

    public function simpleDataProvider()
    {
        return [
            'http2http' => ['http', 'http'],
            'https2http' => ['https', 'http'],
            'http2https' => ['http', 'https'],
            'https2https' => ['https', 'https'],
        ];
    }

    /**
     * @dataProvider simpleDataProvider
     */
    public function testSimple(string $scheme, string $expectedScheme)
    {
        $request = new ServerRequest('GET', '/');
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

        $nr = new class($expectedScheme) implements NetworkResolverInterface
        {

            private $expectedScheme;
            /**
             * @var ServerRequestInterface
             */
            private $serverRequest;

            public function __construct(string $expectedScheme)
            {
                $this->expectedScheme = $expectedScheme;
            }

            /**
             * @return static
             */
            public function withServerRequest(ServerRequestInterface $serverRequest)
            {
                $new = clone $this;
                $new->serverRequest = $serverRequest;
                return $new;
            }

            public function getRemoteIp(): string
            {
                throw new \RuntimeException('Not supported!');
            }

            public function getUserIp(): string
            {
                throw new \RuntimeException('Not supported!');
            }

            public function getServerRequest(): ServerRequestInterface
            {
                return $this->serverRequest->withUri($this->serverRequest->getUri()->withScheme($this->expectedScheme));
            }

            public function isSecureConnection(): bool
            {
                throw new \RuntimeException('Not supported!');
            }
        };
        $middleware = new NetworkResolver($nr);
        $middleware->process($request, $requestHandler);
        $resultRequest = $requestHandler->request;
        /* @var $resultRequest ServerRequestInterface */
        $this->assertSame($expectedScheme, $resultRequest->getUri()->getScheme());
    }

}
