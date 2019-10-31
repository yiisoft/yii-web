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
    public function testEmit()
    {
        $body = 'Example body';
        $response = new Response(200, ['X-Test' => 1], $body);

        ob_start();
        (new SapiEmitter())->emit($response);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(200, http_response_code());
        $this->checkHeadersEquals([
            'X-Test: 1',
            'Content-Length: ' . strlen($body),
        ]);
        $this->assertEquals($body, $result);
    }

    public function testEmptyBodyOnCode()
    {
        $response = new Response(204, ['X-Test' => 1], 'Example body');

        ob_start();
        (new SapiEmitter())->emit($response);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(204, http_response_code());
        $this->checkHeadersEquals([
            'X-Test: 1',
        ]);
        $this->assertEmpty($result);
    }

    public function testEmptyBodyOnFlag()
    {
        $response = new Response(200, ['X-Test' => 1], 'Example body');

        ob_start();
        (new SapiEmitter())->emit($response, true);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(200, http_response_code());
        $this->checkHeadersEquals([
            'X-Test: 1',
        ]);
        $this->assertEmpty($result);
    }

    public function testContentLengthHeader()
    {
        $length = 100;
        $response = new Response(200, ['Content-length' => $length, 'X-Test' => 1], '');

        ob_start();
        (new SapiEmitter())->emit($response);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(200, http_response_code());
        $this->checkHeadersEquals([
            'X-Test: 1',
            'Content-Length: ' . $length,
        ]);
        $this->assertEmpty($result);
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
