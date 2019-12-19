<?php

namespace Yiisoft\Yii\Web\Tests\Helper;

use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\RequestInterface;
use Yiisoft\Yii\Web\Helper\HeaderHelper;
use PHPUnit\Framework\TestCase;

class HeaderHelperTest extends TestCase
{
    public function valueAndParametersDataProvider(): array
    {
        return [
            'empty' => ['', []],
            'noParams' => ['test', ['test']],
            'withParams' => ['test;q=1.0;version=2', ['test', 'q' => '1.0', 'version' => '2']],
            'simple1' => ['audio/*;q=0.2', ['audio/*', 'q' => '0.2']],
            'simple2' => ['gzip;q=1.0', ['gzip', 'q' => '1.0']],
            'simple3' => ['identity;q=0.5', ['identity', 'q' => '0.5']],
            'simple4' => ['*;q=0', ['*', 'q' => '0']],
            'witQuotedParameter' => [
                'test;noqoute=test;qoute="test2"',
                ['test', 'noqoute' => 'test', 'qoute' => 'test2']
            ],

            // 'withSpaces' => ['test; q=1.0; version=2', ['test', 'q' => '1.0', 'version' => '2']],
            // 'quotedValue' => ['"value"', null, \InvalidArgumentException::class],
            // 'valueAsParam' => ['param=value', null, \InvalidArgumentException::class],
            // 'valueAsParam2' => ['param=value;a=b', null, \InvalidArgumentException::class],
            // 'doubleColon' => [': value;a=b', null, \InvalidArgumentException::class],
            'missingDelim1' => ['value; a=a1 b=b1', null, \InvalidArgumentException::class],
            // 'missingDelim2 ' => ['value a=a1', null, \InvalidArgumentException::class],
        ];
    }

    /**
     * @dataProvider valueAndParametersDataProvider
     */
    public function testValueAndParameters(string $input, ?array $expected, ?string $expectedException = null): void
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }
        $this->assertSame($expected, HeaderHelper::getValueAndParameters($input));
    }

    public function sortedValuesAndParametersDataProvider(): array
    {
        return [
            'empty' => ['', []],
            'emptyArray' => [[], []],
            'noQ' => ['text/html,text/xml', [['text/html', 'q' => 1.0], ['text/xml', 'q' => 1.0]]],
            'q' => ['text/html;q=0.2,text/xml', [['text/xml', 'q' => 1.0], ['text/html', 'q' => 0.2]]],
            'qq' => ['text/html;q=0.2,text/xml;q=0.3', [['text/xml', 'q' => 0.3], ['text/html', 'q' => 0.2]]],
            'qqDigits' => ['text/html;q=0.000,text/xml;q=1.000', [['text/xml', 'q' => 1.0], ['text/html', 'q' => 0.0]]],
            'qqDigits0.999' => [
                'text/html;q=0.999,text/xml;q=1.000',
                [['text/xml', 'q' => 1.0], ['text/html', 'q' => 0.999]]
            ],
        ];
    }

    /**
     * @dataProvider sortedValuesAndParametersDataProvider
     */
    public function testSortedValuesAndParameters($input, array $expected): void
    {
        $this->assertSame($expected, HeaderHelper::getSortedValueAndParameters($input));
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
        HeaderHelper::getSortedValueAndParameters($input);
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
            'wildcardRfcExample' => [ //  https://tools.ietf.org/html/rfc7231#section-5.3.2
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

    public function sortedAcceptTypesFromRequestDataProvider(): array
    {
        return [
            'simple' => [
                new ServerRequest('get', '/', ['accept' => ['text/html;q=0.1', 'text/xml']]),
                ['text/xml', 'text/html'],
            ],
        ];
    }

    /**
     * @dataProvider sortedAcceptTypesFromRequestDataProvider
     */
    public function testSortedAcceptTypesFromRequest(RequestInterface $request, array $expected): void
    {
        $this->assertSame($expected, HeaderHelper::getSortedAcceptTypesFromRequest($request));
    }

    public function getParametersDataProvider(): array
    {
        return [
            'simple' => ['a=test;test=test55', ['a' => 'test', 'test' => 'test55']],
            'quoted' => ['a="test";b="test2;";d="."', ['a' => 'test', 'b' => 'test2;', 'd' => '.']],
            'mixed' => ['a=b;c="apple"', ['a' => 'b', 'c' => 'apple']],
            'one' => ['a=test', ['a' => 'test']],
            'oneQuoted' => ['a="test"', ['a' => 'test']],
            'oneQuotedEmpty' => ['a=""', ['a' => '']],
            'oneSingleQuoted' => ["a='a'", ['a' => "'a'"]],
            'mixedQuotes' => [
                'a="tes\'t";test="\'test\'";test2="\\"quoted\\" test"',
                ['a' => 'tes\'t', 'test' => '\'test\'', 'test2' => '"quoted" test']
            ],
            'slashes' => [
                'a="\\t\\e\\s\\t";b="te\\\\st";c="\\"\\"',
                ['a' => 'test', 'b' => 'te\\st', 'c' => '"\\']
            ],
            'specChars' => ['*=test;test=*', ['*' => 'test', 'test' => '*']],
            'specChars2' => ['param*1=a;param*2=b', ['param*1' => 'a', 'param*2' => 'b']],
            'numbers' => ['a=8888;b="999"', ['a' => '8888', 'b' => '999']],
            'invalidQuotes2' => ['a="test', null, \InvalidArgumentException::class],
            'invalidQuotes3' => ['a=test"', null, \InvalidArgumentException::class],
            'invalidQuotes4' => ['a=te"st', null, \InvalidArgumentException::class],
            'invalidEmptyValue' => ['a=b; c=', null, \InvalidArgumentException::class],
            'invalidEmptyParam' => ['a=b; ;c=d', null, \InvalidArgumentException::class],
            'semicolonAtEnd' => ['a=b;', null, \InvalidArgumentException::class],
            'comma' => ['a=test,test', null, \InvalidArgumentException::class],

            # true syntax
            // 'spaces2' => [' a = b ; c = "d" ', ['a' => 'b', 'c' => 'd']],
            # param names should be case insensitive (lowercase?)
            // 'case' => ['A=TEST;TEST=B', ['a' => 'TEST', 'test' => 'B']],
            // 'spaces1' => ['a=b; c="d" ', ['a' => 'b', 'c' => 'd']],
            'spaces3' => ['a=b c', null, \InvalidArgumentException::class],
            'percent' => ['a=%1;b="foo-%32-bar"', ['a' => '%1', 'b' => 'foo-%32-bar']],

            # Invalid syntax but most browsers take a first parameter
            // 'sameName' => ['a=T1;a="T2"', ['a' => 'T1']],
            // 'sameNameCase' => ['aa=T1;Aa="T2"', ['aa' => 'T1']],

            # Invalid syntax but most browsers accept the unquoted value with warn
            # What is better for us to do?
            'brokenToken' => ['a=foo[1](2).html', null, \InvalidArgumentException::class],

            'brokenSyntax1' => ['a==b', null, \InvalidArgumentException::class],
            'brokenSyntax2' => ['a *=b', null, \InvalidArgumentException::class],
            # Invalid syntax but most browsers accept the umlaut with warn
            'brokenToken2' => ['a=foo-ä.html', ['a' => 'foo-ä.html']],
            # Invalid syntax but most browsers accept the umlaut with warn
            'brokenToken3' => ['a=foo-Ã¤.html', ['a' => 'foo-Ã¤.html']],

            # RFC 2231/5987 Encoding: Character Sets
            // '2231iso' => ["filename=iso-8859-1''foo-%E4.html", ['filename' => 'foo-ä.html']],
            // '2231utf' => ["filename=UTF-8''foo-%c3%a4-%e2%82%ac.html", ['filename' => 'foo-ä-€.html']],
            // '2231noc' => ["filename=''foo-%c3%a4-%e2%82%ac.html", null, \InvalidArgumentException::class],
        ];
    }

    /**
     * @dataProvider getParametersDataProvider
     */
    public function testGetParameters(string $input, ?array $expected, ?string $expectedException = null): void
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }
        $this->assertSame($expected, HeaderHelper::getParameters($input));
    }
}
