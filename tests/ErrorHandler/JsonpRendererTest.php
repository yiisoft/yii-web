<?php

namespace Yiisoft\Yii\Web\Tests\ErrorHandler;

use Nyholm\Psr7\ServerRequest;
use Yiisoft\Yii\Web\ErrorHandler\JsonpRenderer;
use Yiisoft\Yii\Web\Tests\ErrorHandler\Mock\ThrowableMock;
use PHPUnit\Framework\TestCase;

class JsonpRendererTest extends TestCase
{

    public function simpleDataProvider(): array
    {
        return [
            'get' => [
                [
                    'callback' => 'testCallback',
                ],
                'callback',
                'testCallback',
                null,
                'testCallback',
            ],
            'overrideGet' => [
                [
                    'callback' => 'testCallback',
                ],
                'callback',
                'testCallback',
                'anyCallback',
                'anyCallback',
            ],
            'callbackNotFound' => [
                [],
                'callback',
                'testCallback',
                null,
                'console.log',
            ],
        ];
    }

    /**
     * @dataProvider simpleDataProvider
     */
    public function testSimple(
        array $queryParams,
        string $callbackParam,
        ?string $callbackGet,
        ?string $callback,
        string $expectedCallback
    ): void {
        $params = [
            'message' => 'test',
            'code' => 879,
        ];
        $throw = ThrowableMock::newInstance($params);
        $request = (new ServerRequest('GET', '/'))->withQueryParams($queryParams);

        $renderer = new JsonpRenderer();
        $renderer->setCallback($callback);
        $renderer->setCallbackParameter($callbackParam);
        $renderer->setRequest($request);
        $result = $renderer->render($throw);
        $this->assertEquals(1,
            \preg_match('/^' . \preg_quote($expectedCallback) . '[(](?<json>.+?)[)]$/sm', $result, $matches));
        $data = json_decode($matches['json'], true);
        $this->assertSame($params['message'], $data['message']);
        $this->assertSame($params['code'], $data['code']);
        $this->assertIsInt($data['line']);
        $this->assertIsString($data['file']);
        $this->assertIsArray($data['trace']);
    }

}
