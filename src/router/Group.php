<?php


namespace yii\web\router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class Group implements RouterInterface
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
     * @var Route[]
     */
    private $routes;

    /**
     * @var Route[]
     */
    private $namedRoutes = [];

    /**
     * Group constructor.
     * @param MiddlewareInterface[] $before
     * @param MiddlewareInterface[] $after
     * @param Route[] $routes
     */
    public function __construct(array $routes, array $before = [], array $after = [])
    {
        $this->before = $before;
        $this->after = $after;

        $this->routes = $routes;
        foreach ($routes as $route) {
            $name = $route->getName();
            if ($name !== null) {
                $this->namedRoutes[$name] = $route;
            }
        }

    }

    public function match(ServerRequestInterface $request): Match
    {
        // TODO: compile regexes into concatenated one
        foreach ($this->routes as $route) {
            if (!in_array($request->getMethod(), $route->getMethods(), true)) {
                continue;
            }

            $host = $route->getHost();
            if ($host !== null && $request->getUri()->getHost() !== $host) {
                continue;
            }

            if (!preg_match($this->getRegex($route), rtrim($request->getUri(), '/'), $matches)) {
                continue;
            }

            $matches = $this->substitutePlaceholderNames($route, $matches);

            foreach ($route->getDefaults() as $name => $value) {
                if (!isset($matches[$name]) || $matches[$name] === '') {
                    $matches[$name] = $value;
                }
            }
            $params = $route->getDefaults();

            if ($route->getCallback() === null) {
                $route = $route->__toString();
                throw new NoHandler("\"$route\" has no handler.");
            }

            return new Match($route->getCallback(), $params, $route->getName());
        }

        throw new NoMatch($request);
    }

    /**
     * Iterates over [[placeholders]] and checks whether each placeholder exists as a key in $matches array.
     * When found - replaces this placeholder key with a appropriate name of matching parameter.
     * Used in [[parseRequest()]], [[createUrl()]].
     *
     * @param array $matches result of `preg_match()` call
     * @return array input array with replaced placeholder keys
     * @see placeholders
     * @since 2.0.7
     */
    protected function substitutePlaceholderNames(Route $route, array $matches)
    {
        foreach ($route->getParameters() as $placeholder => $name) {
            if (isset($matches[$placeholder])) {
                $matches[$name] = $matches[$placeholder];
                unset($matches[$placeholder]);
            }
        }

        return $matches;
    }

    /**
     * Trim slashes in passed string. If string begins with '//', two slashes are left as is
     * in the beginning of a string.
     *
     * @param string $string
     * @return string
     */
    private function trimSlashes($string)
    {
        if (strncmp($string, '//', 2) === 0) {
            return '//' . trim($string, '/');
        }

        return trim($string, '/');
    }

    private function getRegex(Route $route): string
    {
        // TODO: we can include host into pattern as it was in Yii 2
        //$pattern = $this->trimSlashes($this->pattern);

        $pattern = rtrim($route->getPattern(), '/');

        if ($pattern === '') {
            return '#^$#u';
        }

        $pattern = '/' . $pattern . '/';

//        if (strpos($pattern, '<') !== false && preg_match_all('/<([\w._-]+)>/', $pattern, $matches)) {
//            foreach ($matches[1] as $name) {
//                $this->parameters[$name] = "<$name>";
//            }
//        }

        return $this->translatePattern($pattern);
    }

    /**
     * Prepares [[$pattern]] on rule initialization - replace parameter names by placeholders.
     *
     * @param bool $allowAppendSlash Defines position of slash in the param pattern in [[$pattern]].
     * If `false` slash will be placed at the beginning of param pattern. If `true` slash position will be detected
     * depending on non-optional pattern part.
     */
    private function translatePattern(string $pattern, $allowAppendSlash = true)
    {
        $tr = [
            '.' => '\\.',
            '*' => '\\*',
            '$' => '\\$',
            '[' => '\\[',
            ']' => '\\]',
            '(' => '\\(',
            ')' => '\\)',
        ];

        $tr2 = [];
        $requiredPatternPart = $this->pattern;
        $oldOffset = 0;
        if (preg_match_all('/<([\w._-]+):?([^>]+)?>/', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            $appendSlash = false;
            foreach ($matches as $match) {
                $name = $match[1][0];
                $pattern = $match[2][0] ?? '[^\/]+';
                $placeholder = 'a' . hash('crc32b', $name); // placeholder must begin with a letter
                $this->placeholders[$placeholder] = $name;
                if (array_key_exists($name, $this->defaults)) {
                    $length = strlen($match[0][0]);
                    $offset = $match[0][1];
                    $requiredPatternPart = str_replace("/{$match[0][0]}/", '//', $requiredPatternPart);
                    if (
                        $allowAppendSlash
                        && ($appendSlash || $offset === 1)
                        && (($offset - $oldOffset) === 1)
                        && isset($this->pattern[$offset + $length])
                        && $this->pattern[$offset + $length] === '/'
                        && isset($this->pattern[$offset + $length + 1])
                    ) {
                        // if pattern starts from optional params, put slash at the end of param pattern
                        // @see https://github.com/yiisoft/yii2/issues/13086
                        $appendSlash = true;
                        $tr["<$name>/"] = "((?P<$placeholder>$pattern)/)?";
                    } elseif (
                        $offset > 1
                        && $this->pattern[$offset - 1] === '/'
                        && (!isset($this->pattern[$offset + $length]) || $this->pattern[$offset + $length] === '/')
                    ) {
                        $appendSlash = false;
                        $tr["/<$name>"] = "(/(?P<$placeholder>$pattern))?";
                    }
                    $tr["<$name>"] = "(?P<$placeholder>$pattern)?";
                    $oldOffset = $offset + $length;
                } else {
                    $appendSlash = false;
                    $tr["<$name>"] = "(?P<$placeholder>$pattern)";
                }

                if (isset($this->_routeParams[$name])) {
                    $tr2["<$name>"] = "(?P<$placeholder>$pattern)";
                } else {
                    $this->_paramRules[$name] = $pattern === '[^\/]+' ? '' : "#^$pattern$#u";
                }
            }
        }

        // we have only optional params in route - ensure slash position on param patterns
        if ($allowAppendSlash && trim($requiredPatternPart, '/') === '') {
            $this->translatePattern(false);
            return;
        }

        $this->_template = preg_replace('/<([\w._-]+):?([^>]+)?>/', '<$1>', $this->pattern);
        $this->pattern = '#^' . trim(strtr($this->_template, $tr), '/') . '$#u';

        // if host starts with relative scheme, then insert pattern to match any
        if (strncmp($this->host, '//', 2) === 0) {
            $this->pattern = substr_replace($this->pattern, '[\w]+://', 2, 0);
        }

        if (!empty($this->_routeParams)) {
            $this->_routeRule = '#^' . strtr($this->route, $tr2) . '$#u';
        }

    }

    public function generate(string $name, array $parameters = [], string $type = self::TYPE_ABSOLUTE): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new NoRoute($name);
        }

        $route = $this->namedRoutes[$name];

        $parameters = array_merge($route->getDefaults(), $parameters);

        // match the route part first
        if ($route !== $route->route) {
            if ($this->_routeRule !== null && preg_match($this->_routeRule, $route, $matches)) {
                $matches = $this->substitutePlaceholderNames($route, $matches);
                foreach ($this->_routeParams as $name => $token) {
                    if (isset($this->defaults[$name]) && strcmp($this->defaults[$name], $matches[$name]) === 0) {
                        $tr[$token] = '';
                    } else {
                        $tr[$token] = $matches[$name];
                    }
                }
            } else {
                $this->createStatus = self::CREATE_STATUS_ROUTE_MISMATCH;
                return false;
            }
        }

        // match default params
        // if a default param is not in the route pattern, its value must also be matched
        foreach ($this->defaults as $name => $value) {
            if (isset($this->_routeParams[$name])) {
                continue;
            }
            if (!isset($params[$name])) {
                // allow omit empty optional params
                // @see https://github.com/yiisoft/yii2/issues/10970
                if (in_array($name, $this->placeholders) && strcmp($value, '') === 0) {
                    $params[$name] = '';
                } else {
                    $this->createStatus = self::CREATE_STATUS_PARAMS_MISMATCH;
                    return false;
                }
            }
            if (strcmp($params[$name], $value) === 0) { // strcmp will do string conversion automatically
                unset($params[$name]);
                if (isset($this->_paramRules[$name])) {
                    $tr["<$name>"] = '';
                }
            } elseif (!isset($this->_paramRules[$name])) {
                $this->createStatus = self::CREATE_STATUS_PARAMS_MISMATCH;
                return false;
            }
        }

        // match params in the pattern
        foreach ($this->_paramRules as $name => $rule) {
            if (isset($params[$name]) && !is_array($params[$name]) && ($rule === '' || preg_match($rule, $params[$name]))) {
                $tr["<$name>"] = $this->encodeParams ? urlencode($params[$name]) : $params[$name];
                unset($params[$name]);
            } elseif (!isset($this->defaults[$name]) || isset($params[$name])) {
                $this->createStatus = self::CREATE_STATUS_PARAMS_MISMATCH;
                return false;
            }
        }

        $url = $this->trimSlashes(strtr($this->_template, $tr));
        if ($this->host !== null) {
            $pos = strpos($url, '/', 8);
            if ($pos !== false) {
                $url = substr($url, 0, $pos) . preg_replace('#/+#', '/', substr($url, $pos));
            }
        } elseif (strpos($url, '//') !== false) {
            $url = preg_replace('#/+#', '/', trim($url, '/'));
        }

        if (!empty($params) && ($query = http_build_query($params)) !== '') {
            $url .= '?' . $query;
        }

        return $url;
    }
}
