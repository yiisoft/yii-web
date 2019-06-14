<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Web\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Injector\Injector;

/**
 * Callback wraps arbitrary PHP callback into object matching [[MiddlewareInterface]].
 * Usage example:
 *
 * ```php
 * $middleware = new CallbackMiddleware(function(ServerRequestInterface $request, RequestHandlerInterface $handler) {
 *     if ($request->getParams() === []) {
 *         return new Response();
 *     }
 *     return $handler->handle($request);
 * });
 * $response = $middleware->process(Yii::$app->getRequest(), $handler);
 * ```
 *
 * @see MiddlewareInterface
 */
class Callback implements MiddlewareInterface
{
    /**
     * @var callable a PHP callback matching signature of [[MiddlewareInterface::process()]].
     */
    private $callback;

    private $container;

    /**
     * CallbackMiddleware constructor.
     * @param callable $callback
     */
    public function __construct(callable $callback, ContainerInterface $container)
    {
        $this->callback = $callback;
        $this->container = $container;
    }


    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return (new Injector($this->container))->invoke($this->callback, [$request, $handler]);
    }

    public static function __set_state(array $state): self
    {
        return new self($state['callback'], $state['container']);
    }
}
