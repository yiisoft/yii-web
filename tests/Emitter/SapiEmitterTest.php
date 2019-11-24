<?php

namespace Yiisoft\Yii\Web\Tests\Emitter;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\Emitter\SapiEmitter;

/**
 * @runTestsInSeparateProcesses
 */
class SapiEmitterTest extends TestCase
{
    public function testEmit(): void
    {
        $body = 'Example body';
        $response = new Response(200, ['X-Test' => 1], $body);

        (new SapiEmitter())->emit($response);

        $this->assertEquals(200, http_response_code());
        $this->checkHeadersEquals([
            'X-Test: 1',
            'Content-Length: ' . strlen($body),
        ]);
        $this->expectOutputString($body);
    }

    /**
     * @test
     */
    public function shouldNotOutputBodyWhenResponseCodeIs204(): void
    {
        $response = new Response(204, ['X-Test' => 1], 'Example body');

        (new SapiEmitter())->emit($response);

        $this->assertEquals(204, http_response_code());
        $this->checkHeadersEquals([
            'X-Test: 1',
        ]);
        $this->expectOutputString('');
    }

    /**
     * @test
     */
    public function shouldNotOutputBodyIfEmitToldSo(): void
    {
        $response = new Response(200, ['X-Test' => 1], 'Example body');

        (new SapiEmitter())->emit($response, true);

        $this->assertEquals(200, http_response_code());
        $this->checkHeadersEquals([
            'X-Test: 1',
        ]);
        $this->expectOutputString('');
    }

    /**
     * @test
     */
    public function contentLengthShouldNotBeOverwrittenIfPresent(): void
    {
        $length = 100;
        $response = new Response(200, ['Content-length' => $length, 'X-Test' => 1], '');

        (new SapiEmitter())->emit($response);

        $this->assertEquals(200, http_response_code());
        $this->checkHeadersEquals([
            'X-Test: 1',
            'Content-Length: ' . $length,
        ]);
        $this->expectOutputString('');
    }

    /**
     * @test
     */
    public function contentAlwaysShouldFullyEmitted(): void
    {
        $body = 'Example body';
        $response = new Response(200, ['Content-length' => 1, 'X-Test' => 1], $body);

        (new SapiEmitter())->emit($response);

        $this->expectOutputString($body);
    }

    /**
     * @param array $expected
     */
    private function checkHeadersEquals($expected)
    {
        if (function_exists('xdebug_get_headers')) {
            $this->assertEquals($expected, xdebug_get_headers());
        } else {
            $this->markAsRisky();
        }
    }
}
