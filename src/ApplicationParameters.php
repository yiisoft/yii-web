<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web;

use InvalidArgumentException;

use function array_merge;

class ApplicationParameters implements ApplicationParametersInterface
{
    private array $params = [
        'name' => 'My Project',
        'charset' => 'UTF-8',
        'language' => 'en',
        'email' => 'robot@example.com',
        'adminEmail' => 'admin@example.com',
        'infoEmail' => 'info@example.com',
        'supportEmail' => 'support@example.com',
    ];

    public function __construct(array $params)
    {
        $this->params = array_merge($this->params, $params);
    }

    public function get(string $name): string
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        throw new InvalidArgumentException('Application Parameter: "' . $name . '" not found.');
    }

    public function getAdminEmail(): string
    {
        return $this->params['adminEmail'];
    }

    public function getCharset(): string
    {
        return $this->params['charset'];
    }

    public function getEmail(): string
    {
        return $this->params['email'];
    }

    public function getInfoEmail(): string
    {
        return $this->params['infoEmail'];
    }

    public function getLanguage(): string
    {
        return $this->params['language'];
    }

    public function getName(): string
    {
        return $this->params['name'];
    }

    public function getSupportEmail(): string
    {
        return $this->params['supportEmail'];
    }


    public function has(string $name): bool
    {
        return isset($this->params[$name]);
    }

    public function with(string $name, $value): self
    {
        $new = clone $this;
        $new->params[$name] = $value;
        return $new;
    }

    public function withAdminEmail(string $value): self
    {
        return $this->with('adminEmail', $value);
    }

    public function withCharset(string $value): self
    {
        return $this->with('charset', $value);
    }

    public function withEmail(string $value): self
    {
        return $this->with('email', $value);
    }

    public function withInfoEmail(string $value): self
    {
        return $this->with('infoEmail', $value);
    }

    public function withLanguage(string $value): self
    {
        return $this->with('language', $value);
    }

    public function withName(string $value): self
    {
        return $this->with('name', $value);
    }

    public function without(string $name): self
    {
        $new = clone $this;
        unset($new->params[$name]);
        return $new;
    }

    public function withSupportEmail(string $value): self
    {
        return $this->with('supportEmail', $value);
    }
}
