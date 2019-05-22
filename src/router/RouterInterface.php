<?php
namespace yii\web\router;

interface RouterInterface extends UrlGeneratorInterface, UrlMatcherInterface
{
    public function addRoute(Route $route): void;
}
