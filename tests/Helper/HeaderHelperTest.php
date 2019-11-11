<?php

namespace Yiisoft\Yii\Web\Tests\Helper;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Yii\Web\Helper\HeaderHelper;
use Yiisoft\Yii\Web\Middleware\ActionCaller;
use PHPUnit\Framework\TestCase;

class HeaderHelperTest extends TestCase
{
    public function valueAndParametersDataProvider(): array
    {
        return [
            'empty' => ['', []],
            'noParams' => ['test', ['test']],
            'withParams' => ['test;q=1.0;version=2', ['test', 'q' => '1.0', 'version' => '2']],
        ];
    }

    /**
     * @dataProvider valueAndParametersDataProvider
     */
    public function testValueAndParameters(string $input, array $expected): void
    {
        $this->assertSame($expected, HeaderHelper::getValueAndParameters($input));
    }

    public function qFactorSortDataProvider(): array
    {
        return [
            'empty' => ['', false, []],
            'emptyArray' => [[], false, []],
            'noQ' => ['text/html,text/xml', false, [['text/html', 'q' => 1.0], ['text/xml', 'q' => 1.0]]],
            'noQParams' => ['text/html,text/xml', true, ['text/html', 'text/xml']],
            'q' => ['text/html;q=0.2,text/xml', false, [['text/xml', 'q' => 1.0], ['text/html', 'q' => 0.2]]],
            'qq' => ['text/html;q=0.2,text/xml;q=0.3', false, [['text/xml', 'q' => 0.3], ['text/html', 'q' => 0.2]]],
            'qqValues' => ['text/html;q=0.2,text/xml;q=0.3', true, ['text/xml', 'text/html']],
        ];
    }

    /**
     * @dataProvider qFactorSortDataProvider
     */
    public function testQFactorSort($input, bool $valuesOnly, array $expected): void
    {
        $this->assertSame($expected, HeaderHelper::getByQFactorSortedList($input, $valuesOnly));
    }

    public function qFactorSortFailDataProvider(): array
    {
        return [
            'qTooBig' => ['text/xml;q=1.001', \InvalidArgumentException::class],
            'qTooBig2' => ['text/xml;q=2', \InvalidArgumentException::class],
            'qInvalidDigits' => ['text/xml;q=0.0000', \InvalidArgumentException::class],
            'qInvalidDigits2' => ['text/xml;q=1.0000', \InvalidArgumentException::class],
            'int' => [3, \InvalidArgumentException::class],
            'float' => [3.0, \InvalidArgumentException::class],
            'request' => [new ServerRequest('get', '/'), \InvalidArgumentException::class],
        ];
    }

    /**
     * @dataProvider qFactorSortFailDataProvider
     */
    public function testQFactorSortFail($input, string $expected): void
    {
        $this->expectException($expected);
        HeaderHelper::getByQFactorSortedList($input);
    }

    public function sortedAcceptTypesDataProvider(): array
    {
        return [
            'empty' => ['', []],
            'emptyArray' => [[], []],
            'noQ' => ['text/html,text/xml', ['text/html', 'text/xml']],
            'q1' => ['text/html;q=1,text/xml', ['text/html', 'text/xml']],
            'q1End' => ['text/html,text/xml;q=1', ['text/html', 'text/xml']],
            'forward' => ['text/html;q=0.2,text/xml', ['text/xml', 'text/html']],
            'forward2' => ['text/html;q=0.4,text/xml,text/plain;q=0.8', ['text/xml', 'text/plain', 'text/html']],
            'specType' => ['text/html,text/html;version=2', ['text/html;version=2', 'text/html']],
            'specTypeQ' => ['text/html;q=0.3,text/html;version=2;q=0.2', ['text/html', 'text/html;version=2']],
            'qSame' => ['text/html;q=0.4,text/xml;q=0.4', ['text/html', 'text/xml']],
            'specFormatOrder' => [
                'text/html;version=2;a=b,text/html;version=2;a=a',
                ['text/html;a=b;version=2', 'text/html;a=a;version=2']
            ],
            'serverRequest' => [
                new ServerRequest('get', '/', ['accept' => ['text/html;q=0.1', 'text/xml']]),
                ['text/xml', 'text/html'],
            ],
            'wildcard' => [ // https://tools.ietf.org/html/rfc7231#section-5.3.2
                'text/*, text/plain, text/plain;format=flowed, */*',
                [
                    'text/plain;format=flowed',
                    'text/plain',
                    'text/*',
                    '*/*',
                ],
            ],
        ];
    }

    /**
     * @dataProvider sortedAcceptTypesDataProvider
     */
    public function testSortedAcceptTypes($input, array $expected): void
    {
        $this->assertSame($expected, HeaderHelper::getSortedAcceptTypes($input));
    }
}
