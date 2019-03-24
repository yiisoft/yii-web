<?php


namespace yii\web\router;


use Psr\Http\Message\ServerRequestInterface;

class Route implements RouteInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $methods;

    /**
     * @var string
     */
    private $pattern;

    /**
     * @var string
     */
    private $host;

    /**
     * @var callable
     */
    private $callback;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var array
     */
    private $defaults = [];

    private function __construct()
    {
    }

    public static function get(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::GET];
        $new->pattern = $pattern;
        return $new;
    }

    public static function post(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::POST];
        $new->pattern = $pattern;
        return $new;
    }

    public static function put(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::PUT];
        $new->pattern = $pattern;
        return $new;
    }

    public static function delete(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::DELETE];
        $new->pattern = $pattern;
        return $new;
    }

    public static function patch(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::PATCH];
        $new->pattern = $pattern;
        return $new;
    }

    public static function head(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::HEAD];
        $new->pattern = $pattern;
        return $new;
    }

    public static function options(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::OPTIONS];
        $new->pattern = $pattern;
        return $new;
    }

    public static function methods(array $methods, string $pattern): self
    {
        // TODO: should we validate methods?
        $new = new static();
        $new->methods = $methods;
        $new->pattern = $pattern;
        return $new;
    }

    public static function allMethods(string $pattern): self
    {
        $new = new static();
        $new->methods = Method::ALL;
        $new->pattern = $pattern;
        return $new;
    }

    public function name(string $name): self
    {
        $new = clone $this;
        $new->name = $name;
        return $new;
    }

    public function host(string $host): self
    {
        $new = clone $this;
        $new->host = rtrim($host, '/');
        return $new;
    }

    /**
     * Parameter validation rules indexed by parameter names
     *
     * @param array $parameters
     * @return Route
     */
    public function parameters(array $parameters): self
    {
        $new = clone $this;
        $new->parameters = $parameters;
        return $new;
    }

    /**
     * Parameter default values indexed by parameter names
     *
     * @param array $defaults
     * @return Route
     */
    public function defaults(array $defaults): self
    {
        $new = clone $this;
        $new->defaults = $defaults;
        return $new;
    }

    /**
     * // TODO: should we allow adding middlewares here?
     *
     * @param callable $callback
     * @return Route
     */
    public function to(callable $callback): self
    {
        $new = clone $this;
        $new->callback = $callback;
        return $new;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function generate(array $parameters = [], string $type = self::TYPE_ABSOLUTE): string
    {
        $parameters = array_merge($this->defaults, $parameters);

        // match the route part first
        if ($route !== $this->route) {
            if ($this->_routeRule !== null && preg_match($this->_routeRule, $route, $matches)) {
                $matches = $this->substitutePlaceholderNames($matches);
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

    public function match(ServerRequestInterface $request): ?Match
    {
        if (!in_array($request->getMethod(), $this->methods, true)) {
            return null;
        }

        // TODO: match with regexp
        if ($this->host !== null && $request->getUri()->getHost() !== $this->host) {
            return null;
        }

        if (!preg_match($this->getRegex(), rtrim($request->getUri(), '/'), $matches)) {
            return null;
        }

        $matches = $this->substitutePlaceholderNames($matches);

        foreach ($this->defaults as $name => $value) {
            if (!isset($matches[$name]) || $matches[$name] === '') {
                $matches[$name] = $value;
            }
        }
        $params = $this->defaults;

        if ($this->callback === null) {
            $route = $this->__toString();
            throw new NoHandler("\"$route\" has no handler.");
        }

        return new Match($this->callback, $params, $this->name);
    }

    public function __toString()
    {
        $result = '';

        if ($this->name !== null) {
            $result .= $this->name . ' ';
        }

        if ($this->methods !== null) {
            $result .= implode(',', $this->methods) . ' ';
        }
        if ($this->host !== null && strrpos($this->pattern, $this->host) === false) {
            $result .= $this->host . '/';
        }
        $result .= $this->pattern;

        if ($result === '') {
            return '/';
        }

        return $result;
    }

    private function getRegex(): string
    {
        // TODO: we can include host into pattern as it was in Yii 2
        //$pattern = $this->trimSlashes($this->pattern);

        $pattern = rtrim($this->pattern, '/');

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
    protected function substitutePlaceholderNames(array $matches)
    {
        foreach ($this->parameters as $placeholder => $name) {
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

}