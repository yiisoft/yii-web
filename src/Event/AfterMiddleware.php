<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Event;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * AfterMiddleware event is raised after a middleware was executed.
 */
final class AfterMiddleware
{
    private MiddlewareInterface $middleware;
    private ?ResponseInterface $response;

    public function __construct(MiddlewareInterface $middleware, ?ResponseInterface $response)
    {
        $this->middleware = $middleware;
        $this->response = $response;
    }

    /**
     * @return MiddlewareInterface middleware that was executed
     */
    public function getMiddleware(): MiddlewareInterface
    {
        return $this->middleware;
    }

    /**
     * @return ResponseInterface|null response generated by middleware or null in case there was an error
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
