<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Web\Middleware\TagRequest;

class TagRequestTest extends TestCase
{
    public function testProcess(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects($this->once())
            ->method('withAttribute')
            ->with(
                $this->equalTo('requestTag'),
                $this->isType('string')
            )
            ->willReturnSelf();

        $handler = $this->createMock(RequestHandlerInterface::class);

        (new TagRequest())->process($request, $handler);
    }
}
