<?php

namespace yii\web\router;


use Psr\Http\Message\ServerRequestInterface;

interface UrlMatcherInterface
{
    public function match(ServerRequestInterface $request): Match;
}
