<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Mock;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MockMiddleware implements MiddlewareInterface
{
    private int $responseCode;

    public function __construct(int $responseCode = 200)
    {
        $this->responseCode = $responseCode;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return new Response($this->responseCode);
    }
}
