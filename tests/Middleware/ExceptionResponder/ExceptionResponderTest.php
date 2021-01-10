<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Middleware\ExceptionResponder;

use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Web\Middleware\ExceptionResponder;

final class ExceptionResponderTest extends TestCase
{
    public function testCode(): void
    {
        $middleware = $this->createMiddleware([
            TestException::class => Status::BAD_REQUEST,
        ]);

        $this->assertSame(Status::BAD_REQUEST, $this->process($middleware)->getStatusCode());
    }

    public function testCallable(): void
    {
        $middleware = $this->createMiddleware([
            TestException::class => function (ResponseFactoryInterface $responseFactory) {
                return $responseFactory->createResponse(Status::CREATED);
            },
        ]);

        $this->assertSame(Status::CREATED, $this->process($middleware)->getStatusCode());
    }

    public function testAnotherException(): void
    {
        $middleware = $this->createMiddleware([
            InvalidArgumentException::class => Status::BAD_REQUEST,
        ]);

        $this->expectException(TestException::class);
        $this->process($middleware);
    }

    private function process(ExceptionResponder $middleware): ResponseInterface
    {
        return $middleware->process(
            (new Psr17Factory())->createServerRequest(Method::GET, 'http://example.com'),
            new class() implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    throw new TestException();
                }
            }
        );
    }

    private function createMiddleware(array $exceptionMap): ExceptionResponder
    {
        return new ExceptionResponder(
            $exceptionMap,
            new Psr17Factory(),
            new Injector(
                new Container([
                    ResponseFactoryInterface::class => Psr17Factory::class,
                ]),
            ),
        );
    }
}
