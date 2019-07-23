<?php

namespace Yiisoft\Yii\Web\Tests;


use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;

class HtmlRendererTest extends TestCase
{
    public function testRenderCurl(): void
    {
        $renderer = new HtmlRenderer();
        $renderer->setRequest(new ServerRequest('GET', 'http://example.com'));
        $curl = $renderer->renderCurl();
        $this->assertEquals('curl http://example.com', $curl);
    }
}