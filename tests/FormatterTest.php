<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests;

use yii\web\Response;
use yii\web\ResponseFormatterInterface;

abstract class FormatterTest extends \yii\tests\TestCase
{
    /**
     * @var Response
     */
    public $response;
    /**
     * @var ResponseFormatterInterface
     */
    public $formatter;

    protected function setUp()
    {
        $this->mockApplication();
        $this->response = $this->factory->create(Response::class);
        $this->formatter = $this->getFormatterInstance();
    }

    /**
     * @return ResponseFormatterInterface
     */
    abstract protected function getFormatterInstance();

    /**
     * Formatter should not format null.
     */
    public function testFormatNull()
    {
        $this->response->data = null;
        $this->formatter->format($this->response);
        $this->assertSame('', $this->response->content);
    }

    /**
     * @param mixed  $data the data to be formatted
     * @param string $json the expected JSON body
     * @dataProvider formatScalarDataProvider
     */
    public function testFormatScalar($data, $json)
    {
        $this->response->data = $data;
        $this->formatter->format($this->response);
        $this->assertEquals($json, $this->response->content);
    }

    /**
     * @param mixed  $data the data to be formatted
     * @param string $json the expected JSON body
     * @dataProvider formatArrayDataProvider
     */
    public function testFormatArrays($data, $json)
    {
        $this->response->data = $data;
        $this->formatter->format($this->response);
        $this->assertEquals($json, $this->response->content);
    }

    /**
     * @param mixed  $data the data to be formatted
     * @param string $json the expected JSON body
     * @dataProvider formatTraversableObjectDataProvider
     */
    public function testFormatTraversableObjects($data, $json)
    {
        $this->response->data = $data;
        $this->formatter->format($this->response);
        $this->assertEquals($json, $this->response->content);
    }

    /**
     * @param mixed  $data the data to be formatted
     * @param string $json the expected JSON body
     * @dataProvider formatObjectDataProvider
     */
    public function testFormatObjects($data, $json)
    {
        $this->response->data = $data;
        $this->formatter->format($this->response);
        $this->assertEquals($json, $this->response->content);
    }

    /**
     * @param mixed  $data the data to be formatted
     * @param string $expectedResult the expected body
     * @dataProvider formatModelDataProvider
     */
    public function testFormatModels($data, $expectedResult)
    {
        $this->response->data = $data;
        $this->formatter->format($this->response);
        $this->assertEquals($expectedResult, $this->response->content);
    }
}
