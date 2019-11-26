<?php

namespace Yiisoft\Yii\Web\Emitter;

use Yiisoft\Yii\Web\Tests\Emitter\HTTPFunctions;

/**
 * Mock for the headers_sent() function for Emitter class.
 */
function headers_sent(): bool
{
    return false;
}
/**
 * Mock for the header() function for Emitter class.
 */
function header(string $string, bool $replace = true, ?int $http_response_code = null): void
{
    HTTPFunctions::header($string, $replace, $http_response_code);
}
/**
 * Mock for the headers_sent() function for Emitter class.
 */
function header_remove(): void
{
    HTTPFunctions::header_remove();
}
/**
 * Mock for the header_list() function for Emitter class.
 */
function header_list(): array
{
    return HTTPFunctions::headers_list();
}
/**
 * Mock for the http_response_code() function for Emitter class.
 */
function http_response_code(?int $response_code = null): int
{
    return HTTPFunctions::http_response_code($response_code);
}

namespace Yiisoft\Yii\Web\Tests\Emitter;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Yiisoft\Yii\Web\Emitter\SapiEmitter;

/**
 * @runTestsInSeparateProcesses
 */
class SapiEmitterTest extends TestCase
{
    public function setUp(): void
    {
        HTTPFunctions::reset();
    }

    public function tearDown(): void
    {
        HTTPFunctions::reset();
    }

    public function testEmit(): void
    {
        $body = 'Example body';
        $response = $this->createResponse(200, ['X-Test' => 1], $body);

        $this->createEmitter()->emit($response);

        $this->checkResponseCodeEquals(200);
        $this->checkHeadersEquals([
            'X-Test: 1',
            'Content-Length: ' . strlen($body),
        ]);
        $this->expectOutputString($body);
    }

    /**
     * @test
    //  */
    public function shouldNotOutputBodyWhenResponseCodeIs204(): void
    {
        $response = $this->createResponse(204, ['X-Test' => 1], 'Example body');

        $this->createEmitter()->emit($response);

        $this->checkResponseCodeEquals(204);
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

        $this->checkResponseCodeEquals(200);
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
        $response = $this->createResponse(200, ['Content-Length' => $length, 'X-Test' => 1], '');

        $this->createEmitter()->emit($response);

        $this->checkResponseCodeEquals(200);
        $this->checkHeadersEquals([
            'X-Test: 1',
            'Content-Length: ' . $length,
        ]);
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

    private function createEmitter(?int $bufferSize = null): SapiEmitter
    {
        return new SapiEmitter($bufferSize);
    }

    private function createResponse(
        int $status = 200,
        array $headers = [],
        $body = null,
        string $version = '1.1'
    ): ResponseInterface
    {
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

    private function checkHeadersEquals(array $expected): void
    {
        $actual = HTTPFunctions::headers_list();
        sort($expected, SORT_STRING);
        sort($actual, SORT_STRING);
        $this->assertEquals($expected, $actual);
    }

    private function checkResponseCodeEquals(int $expected): void
    {
        $this->assertEquals($expected, HTTPFunctions::http_response_code());
    }
}
