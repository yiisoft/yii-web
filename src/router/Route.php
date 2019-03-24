<?php


namespace yii\web\router;

/**
 * Route defines a mapping from URL to callback / name and vice versa
 */
class Route
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
     * TODO: should it be optional?
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

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return string
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return callable
     */
    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }
}
