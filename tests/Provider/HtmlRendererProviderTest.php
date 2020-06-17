<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Provider;

use Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer;
use Yiisoft\Yii\Web\Tests\TestCase;

final class HtmlRendererProviderTest extends TestCase
{
    public function testProviderConfig(): void
    {
        $this->assertNotEmpty($this->container->get(HtmlRenderer::class));
    }
}
