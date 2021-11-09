# ravendb-php-client

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

**Note:** Replace ```Aleksandar Sabo``` ```alxsabo``` ```https://github.com/alxsabo``` ```alxsabo@gmail.com``` ```ravendb``` ```ravendb-php-client``` ```RavenDB PHP client``` with their correct values in [README.md](README.md), [CHANGELOG.md](CHANGELOG.md), [CONTRIBUTING.md](CONTRIBUTING.md), [LICENSE.md](LICENSE.md) and [composer.json](composer.json) files, then delete this line. You can run `$ php prefill.php` in the command line to make all replacements at once. Delete the file prefill.php as well.

This is where your description should go. Try and limit it to a paragraph or two, and maybe throw in a mention of what
PSRs you support to avoid any confusion with users and contributors.

## Structure

If any of the following are applicable to your project, then the directory structure should follow industry best practices by being named the following.

```
bin/        
build/
docs/
config/
src/
tests/
vendor/
```


## Install

Via Composer

``` bash
$ composer require ravendb/ravendb-php-client
```

## Usage

``` php
$skeleton = new Raven\Raven();
echo $skeleton->echoPhrase('Hello, League!');
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Coding style

To check coding style run:

``` bash
$ composer check-style
```

If there are some errors, most of them can be fixed by running:

``` bash
$ composer fix-style
```

## Static analyse

In order to analyse code with PHPStan tool run

``` bash
$ composer stan
```

You can use also Psalm analyser with

``` bash
$ composer psalm
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email alxsabo@gmail.com instead of using the issue tracker.

## Credits

- [Aleksandar Sabo][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/ravendb/ravendb-php-client.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/ravendb/ravendb-php-client/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/ravendb/ravendb-php-client.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/ravendb/ravendb-php-client.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/ravendb/ravendb-php-client.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/ravendb/ravendb-php-client
[link-travis]: https://travis-ci.org/ravendb/ravendb-php-client
[link-scrutinizer]: https://scrutinizer-ci.com/g/ravendb/ravendb-php-client/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/ravendb/ravendb-php-client
[link-downloads]: https://packagist.org/packages/ravendb/ravendb-php-client
[link-author]: https://github.com/alxsabo
[link-contributors]: ../../contributors
