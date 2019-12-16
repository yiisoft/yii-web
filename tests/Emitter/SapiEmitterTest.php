<?php
namespace Yiisoft\Yii\Web\Tests\Emitter;

include 'httpFunctionMocks.php';

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Yii\Web\Emitter\EmitterInterface;
use Yiisoft\Yii\Web\Emitter\SapiEmitter;
use Yiisoft\Yii\Web\Exception\HeadersHaveBeenSentException;

/**
 * @runTestsInSeparateProcesses
 */
class SapiEmitterTest extends TestCase
{
    public function setUp(): void
    {
        HTTPFunctions::reset();
    }

    public static function tearDownAfterClass(): void
    {
        HTTPFunctions::reset();
    }

    public function noBodyHTTPCodeProvider(): array
    {
        return [[100], [101], [102], [204], [205], [304]];
    }

    public function testEmit(): void
    {
        $body = 'Example body';
        $response = $this->createResponse(200, ['X-Test' => 1], $body);

        $this->createEmitter()->emit($response);

        $this->assertEquals(200, $this->getResponseCode());
        $this->assertCount(2, $this->getHeaders());
        $this->assertContains('X-Test: 1', $this->getHeaders());
        $this->assertContains('Content-Length: ' . strlen($body), $this->getHeaders());
        $this->expectOutputString($body);
    }

    /**
     * @test
     * @dataProvider noBodyHTTPCodeProvider
     */
    public function shouldNotOutputBodyByResponseCode(int $code): void
    {
        $response = $this->createResponse($code, ['X-Test' => 1], 'Example body');

        $this->createEmitter()->emit($response);

        $this->assertEquals($code, $this->getResponseCode());
        $this->assertTrue(HTTPFunctions::hasHeader('X-Test'));
        $this->assertFalse(HTTPFunctions::hasHeader('Content-Length'));
        $this->expectOutputString('');
    }

    /**
     * @test
     */
    public function shouldNotOutputBodyAndContentLengthIfEmitToldSo(): void
    {
        $response = $this->createResponse(200, ['X-Test' => 1], 'Example body');

        $this->createEmitter()->emit($response, true);

        $this->assertEquals(200, $this->getResponseCode());
        $this->assertTrue(HTTPFunctions::hasHeader('X-Test'));
        $this->assertFalse(HTTPFunctions::hasHeader('Content-Length'));
        $this->expectOutputString('');
    }

    /**
     * @test
     */
    public function contentLengthShouldNotBeOverwrittenIfPresent(): void
    {
        $length = 100;
        $response = $this->createResponse(200, ['Content-Length' => $length, 'X-Test' => 1], 'Example body');

        $this->createEmitter()->emit($response);

        $this->assertEquals(200, $this->getResponseCode());
        $this->assertCount(2, $this->getHeaders());
        $this->assertContains('X-Test: 1', $this->getHeaders());
        $this->assertContains('Content-Length: ' . $length, $this->getHeaders());
        $this->expectOutputString('Example body');
    }

    /**
     * @test
     */
    public function contentLengthShouldNotOutputWhenBodyIsEmpty(): void
    {
        $length = 100;
        $response = $this->createResponse(200, ['Content-Length' => $length, 'X-Test' => 1], '');

        $this->createEmitter()->emit($response);

        $this->assertEquals(200, $this->getResponseCode());
        $this->assertEquals(['X-Test: 1'], $this->getHeaders());
        $this->expectOutputString('');
    }

    /**
     * @test
     */
    public function contentAlwaysShouldBeFullyEmitted(): void
    {
        $body = 'Example body';
        $response = $this->createResponse(200, ['Content-length' => 1, 'X-Test' => 1], $body);

        $this->createEmitter()->emit($response);

        $this->expectOutputString($body);
    }

    /**
     * @test
     */
    public function sentHeadersShouldBeRemoved(): void
    {
        HTTPFunctions::header('Cookie-Set: First Cookie');
        HTTPFunctions::header('X-Test: 1');
        $body = 'Example body';
        $response = $this->createResponse(200, [], $body);

        $this->createEmitter()->emit($response);

        $this->assertEquals(['Content-Length: ' . strlen($body)], $this->getHeaders());
        $this->expectOutputString($body);
    }

    /**
     * @test
     */
    public function exceptionWhenHeadersHaveBeenSent(): void
    {
        $body = 'Example body';
        $response = $this->createResponse(200, [], $body);
        HTTPFunctions::set_headers_sent(true, 'test-file.php', 200);

        $this->expectException(HeadersHaveBeenSentException::class);
        $this->createEmitter()->emit($response);
    }

    /**
     * @test
     */
    public function shouldEmitDuplicateHeaders(): void
    {
        $body = 'Example body';
        $response = $this->createResponse(200, [], $body)
                         ->withHeader('X-Test', '1')
                         ->withAddedHeader('X-Test', '2')
                         ->withAddedHeader('X-Test', '3; 3.5')
                         ->withHeader('Cookie-Set', '1')
                         ->withAddedHeader('cookie-Set', '2')
                         ->withAddedHeader('Cookie-set', '3');

        $this->createEmitter()->emit($response);
        $this->assertEquals(200, $this->getResponseCode());
        $this->assertContains('X-Test: 1', $this->getHeaders());
        $this->assertContains('X-Test: 2', $this->getHeaders());
        $this->assertContains('X-Test: 3; 3.5', $this->getHeaders());
        $this->assertContains('Cookie-Set: 1', $this->getHeaders());
        $this->assertContains('Cookie-Set: 2', $this->getHeaders());
        $this->assertContains('Cookie-Set: 3', $this->getHeaders());
        $this->assertContains('Content-Length: ' . strlen($body), $this->getHeaders());
        $this->expectOutputString($body);
    }

    private function createEmitter(?int $bufferSize = null): EmitterInterface
    {
        return new SapiEmitter($bufferSize);
    }

    private function createResponse(
        int $status = 200,
        array $headers = [],
        $body = null,
        string $version = '1.1'
    ): ResponseInterface {
        $response = (new Response())
            ->withStatus($status)
            ->withProtocolVersion($version);
        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }
        if ($body instanceof StreamInterface) {
            $response = $response->withBody($body);
        } elseif (is_string($body)) {
            $response->getBody()->write($body);
        }
        return $response;
    }

    private function getHeaders(): array
    {
        return HTTPFunctions::headers_list();
    }

    private function getResponseCode(): int
    {
        return HTTPFunctions::http_response_code();
    }
}
