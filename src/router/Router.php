<?php


namespace yii\web\router;

use Psr\Http\Message\ServerRequestInterface;

class Router implements RouterInterface
{
    /**
     * @var Group[]
     */
    private $groups;

    /**
     * Router constructor.
     * @param Group[] $groups
     */
    public function __construct(array $groups)
    {
        $this->groups = $groups;
    }


    public function match(ServerRequestInterface $request): Match
    {
        foreach ($this->groups as $group) {
            try {
                return $group->match($request);
            } catch (NoMatch $e) {
                // ignore
            }
        }
        throw new NoMatch($request);
    }

    public function generate(string $name, array $parameters = [], string $type = self::TYPE_ABSOLUTE): string
    {
        foreach ($this->groups as $group) {
            try {
                return $group->generate($name, $parameters, $type);
            } catch (NoRoute $e) {
                // ignore
            }
        }
        throw new NoRoute($name);
    }
}
