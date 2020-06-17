<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Provider;

use Yiisoft\Yii\Web\Session\SessionInterface;
use Yiisoft\Yii\Web\Tests\TestCase;

final class SessionProviderTest extends TestCase
{
    public function testProviderConfig(): void
    {
        $this->assertNotEmpty($this->container->get(SessionInterface::class));
    }
}
