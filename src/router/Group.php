<?php


namespace yii\router;

use Psr\Http\Server\MiddlewareInterface;

class Group
{
    /**
     * @var MiddlewareInterface[]
     */
    private $before = [];

    /**
     * @var MiddlewareInterface[]
     */
    private $after = [];

    /**
     * @var RouteInterface[]
     */
    private $routes = [];
}
