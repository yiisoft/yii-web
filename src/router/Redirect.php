<?php

namespace yii\web\router;

final class Redirect
{
    private $status = 301;

    private $routeName;
    private $routeParameters = [];

    private $url;

    private function __construct()
    {
    }

    public static function toRoute(string $name, array $parameters = [])
    {
        $new = new static();
        $new->routeName = $name;
        $new->routeParameters = $parameters;

        return $new;
    }

    public static function toUrl(string $url)
    {
        $new = new static();
        $new->url = $url;

        return $new;
    }

    public function withStatus(int $status)
    {
        $new = clone $this;
        $new->status = $status;

        return $new;
    }

    public function __invoke(...$arguments)
    {
        // TODO: how to implement redirection here?
    }
}
