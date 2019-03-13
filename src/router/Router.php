<?php


namespace yii\router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;

class Router implements RouterInterface
{
    /**
     * @var RouteInterface[]
     */
    private $routes;

    /**
     * @var CacheInterface
     */
    private $cache;

    public function match(ServerRequestInterface $request): Match
    {
        // TODO: we can pre-build "classic" routes into big regex chunks as nikic did in his router
        foreach ($this->routes as $route) {
            $match = $route->match($request);
            if ($match !== null) {
                return $match;
            }
        }
        throw new NoMatch($request);
    }

    public function generate(string $name, array $parameters = [], string $type = self::TYPE_ABSOLUTE): string
    {
        // TODO: named routes could be indexed by name separately on adding routes
        foreach ($this->routes as $routeName => $route) {
            if ($route->getName() === $name) {
                return $route->generate($parameters, $type);
            }
        }
        throw new NoRoute($name);
    }
}
