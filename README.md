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
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-web%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/yii-web/master)
[![static analysis](https://github.com/yiisoft/yii-web/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-web/actions?query=workflow%3A%22static+analysis%22)


Installation
------------

- The minimum required PHP version of Yii 3.0 is PHP 7.4.
- [Follow the Definitive Guide](https://github.com/yiisoft/docs/tree/master/guide/en)
in order to get step by step instructions.

Documentation
-------------

- A [Definitive Guide](https://github.com/yiisoft/docs/tree/master/guide/en) and 
a [Class Reference](#) cover every detail
of the framework.

Community
---------

- Participate in [discussions at forums](https://www.yiiframework.com/forum/).
- [Community Slack](https://join.slack.com/t/yii/shared_invite/MjIxMjMxMTk5MTU1LTE1MDE3MDAwMzMtM2VkMTMyMjY1Ng) and [Chat in IRC](https://www.yiiframework.com/chat/).
- Follow us on [Facebook](https://www.facebook.com/groups/yiitalk/), [Twitter](https://twitter.com/yiiframework)
and [GitHub](https://github.com/yiisoft/yii2).
- Check [other communities](https://github.com/yiisoft/yii2/wiki/communities).

Contributing
------------

The framework is [Open Source](LICENSE.md) powered by [an excellent community](https://github.com/yiisoft/yii2/graphs/contributors).

You may join us and:

- [Report an issue](docs/internals/report-an-issue.md)
- [Translate documentation or messages](docs/internals/translation-workflow.md)
- [Give us feedback or start a design discussion](http://www.yiiframework.com/forum/index.php/forum/42-general-discussions-for-yii-20/)
- [Contribute to the core code or fix bugs](docs/internals/git-workflow.md)

### Reporting Security issues

Please refer to a [special page at the website](https://www.yiiframework.com/security/)
describing proper workflow for security issue reports.

### Directory Structure

```
build/               internally used build tools
docs/                documentation
framework/           core framework code
tests/               tests of the core framework code
```

### Spreading the Word

Acknowledging or citing Yii is as important as direct contributions.

**In presentations**

If you are giving a presentation or talk featuring work that makes use of Yii and would like to acknowledge it,
we suggest using [our logo](https://www.yiiframework.com/logo/) on your title slide.

**In projects**

If you are using Yii as part of an OpenSource project, a way to acknowledge it is to
[use a special badge](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat) in your README:    

![Yii](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)

If your code is hosted at GitHub, you can place the following in your README.md file to get the badge:

```
[![Yii](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](http://www.yiiframework.com/)
```

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```php
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```php
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```php
./vendor/bin/psalm
```

## License

The Yii Web is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
