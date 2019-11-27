<?php
namespace Yiisoft\Yii\Web\Tests\Emitter;

include 'includeMocks.php';

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class HTTPFunctionsTest extends TestCase
{
    public function setUp(): void
    {
        HTTPFunctions::reset();
    }

    public static function tearDownAfterClass(): void
    {
        HTTPFunctions::reset();
    }

    public function testReset(): void
    {
        // check initial state
        $this->assertEquals(200, $this->getResponseCode());
        $this->assertEquals([], $this->getHeaders());

        HTTPFunctions::header('X-Test: 3', false, 404);

        $this->assertEquals(404, $this->getResponseCode());
        $this->assertEquals(['X-Test: 3'], $this->getHeaders());

        HTTPFunctions::reset();

        $this->assertEquals(200, $this->getResponseCode());
        $this->assertEquals([], $this->getHeaders());
    }

    public function testHeaderAndHasHeader(): void
    {
        $this->assertFalse(HTTPFunctions::hasHeader('x-test'));

        HTTPFunctions::header('X-Test: 1');

        $this->assertTrue(HTTPFunctions::hasHeader('x-test'));
    }

    public function testAddedHeaders(): void
    {
        // first header
        HTTPFunctions::header('X-Test: 1');
        // added header, change status
        HTTPFunctions::header('X-Test: 2', false, 300);
        HTTPFunctions::header('X-Test: 3', false);

        $this->assertContains('X-Test: 1', $this->getHeaders());
        $this->assertContains('X-Test: 2', $this->getHeaders());
        $this->assertContains('X-Test: 3', $this->getHeaders());
        $this->assertEquals(300, $this->getResponseCode());

        // replace x-test headers, change status
        HTTPFunctions::header('X-Test: 3', true, 404);
        $this->assertEquals(['X-Test: 3'], $this->getHeaders());
        $this->assertEquals(404, $this->getResponseCode());
    }

    public function testHeaderRemove(): void
    {
        HTTPFunctions::header('X-Test: 1');
        HTTPFunctions::header('Y-Test: 2');
        HTTPFunctions::header('Z-Test: 3', false, 404);

        HTTPFunctions::header_remove('y-test');
        $this->assertEquals(['X-Test: 1', 'Z-Test: 3'], $this->getHeaders());

        HTTPFunctions::header_remove();

        $this->assertEquals(404, $this->getResponseCode());
        $this->assertEquals([], $this->getHeaders());
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
