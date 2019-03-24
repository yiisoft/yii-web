<?php

namespace yii\web\router;

use Psr\Http\Message\ServerRequestInterface;

interface UrlMatcherInterface
{
    /**
     * @param ServerRequestInterface $request
     *
     * @throws NoHandler
     * @throws NoMatch
     *
     * @return Match
     */
    public function match(ServerRequestInterface $request): Match;
}
