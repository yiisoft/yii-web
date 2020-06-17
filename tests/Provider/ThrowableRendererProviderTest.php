<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Provider;

use Yiisoft\Yii\Web\ErrorHandler\ThrowableRendererInterface;
use Yiisoft\Yii\Web\Tests\TestCase;

final class ThrowableRendererProviderTest extends TestCase
{
    public function testProviderConfig(): void
    {
        $this->assertNotEmpty($this->container->get(ThrowableRendererInterface::class));
    }
}
