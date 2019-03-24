<?php


namespace yii\web\router;


use Psr\Http\Message\ServerRequestInterface;

interface RouteInterface
{
    public const TYPE_ABSOLUTE = 'absolute';
    public const TYPE_RELATIVE = 'relative';

    public function getName(): ?string;
    public function match(ServerRequestInterface $request): ?Match;
    public function generate(array $parameters = [], string $type = self::TYPE_ABSOLUTE): string;
}
