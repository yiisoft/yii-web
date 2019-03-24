<?php


namespace yii\web\router;

use Psr\Http\Server\MiddlewareInterface;

class Group
{
    /**
     * @var MiddlewareInterface[]
     */
    private $before;

    /**
     * @var MiddlewareInterface[]
     */
    private $after;

    /**
     * @var RouteInterface[]
     */
    private $routes;

    /**
     * Group constructor.
     * @param MiddlewareInterface[] $before
     * @param MiddlewareInterface[] $after
     * @param RouteInterface[] $routes
     */
    public function __construct(array $routes, array $before, array $after)
    {
        $this->before = $before;
        $this->after = $after;
        $this->routes = $routes;
    }
}
