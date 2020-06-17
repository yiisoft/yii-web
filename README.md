<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii Framework Web Extension</h1>
    <br>
</p>

[Yii Framework] is a modern framework designed to be a solid foundation for your PHP application.

This [Yii Framework] extension allows easy creation of web applications.

[Yii Framework]: https://github.com/yiisoft/core

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-web/v/stable.png)](https://packagist.org/packages/yiisoft/yii-web)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-web/downloads.png)](https://packagist.org/packages/yiisoft/yii-web)
[![Build Status](https://github.com/yiisoft/yii-web/workflows/build/badge.svg)](https://github.com/yiisoft/yii-web/actions?query=workflow%3Abuild)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/yiisoft/yii-web/badges/quality-score.png?s=b1074a1ff6d0b214d54fa5ab7abbb90fc092471d)](https://scrutinizer-ci.com/g/yiisoft/yii-web/)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/yii-web/badges/coverage.png?s=31d80f1036099e9d6a3e4d7738f6b000b3c3d10e)](https://scrutinizer-ci.com/g/yiisoft/yii-web/)
[![static analysis](https://github.com/yiisoft/yii-web/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-web/actions?query=workflow%3A%22static+analysis%22)


### Installation

The package could be installed via composer:

```php
composer require yiisoft/yii-wev
```

### Config components

#### Session

```php
'yiisoft/yii-web' => [
    'session' => [
        'options' => [
            'use_cookies' => 1,
            'cookie_secure' => 1,
            'use_only_cookies' => 1,
            'cookie_httponly' => 1,
            'use_strict_mode' => 1,
            'sid_bits_per_character' => 5,
            'sid_length' => 48,
            'cache_limiter' => 'nocache',
            'cookie_samesite' => 'Lax',
        ],
        'handler' => null
    ],
]
```

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```php
./vendor/bin/phpunit
```

### Static analysis

The code is statically analyzed with [Phan](https://github.com/phan/phan/wiki). To run static analysis:

```php
./vendor/bin/phan
```
