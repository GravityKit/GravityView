[![Latest Stable Version](https://poser.pugx.org/phpcompatibility/phpcompatibility-paragonie/v/stable.png)](https://packagist.org/packages/phpcompatibility/phpcompatibility-paragonie)
[![Latest Unstable Version](https://poser.pugx.org/phpcompatibility/phpcompatibility-paragonie/v/unstable.png)](https://packagist.org/packages/phpcompatibility/phpcompatibility-paragonie)
[![License](https://poser.pugx.org/phpcompatibility/phpcompatibility-paragonie/license.png)](https://github.com/PHPCompatibility/PHPCompatibilityParagonie/blob/master/LICENSE)
[![Build Status](https://travis-ci.org/PHPCompatibility/PHPCompatibilityParagonie.svg?branch=master)](https://travis-ci.org/PHPCompatibility/PHPCompatibilityParagonie)

# PHPCompatibilityParagonie

Using PHPCompatibilityParagonie, you can analyse the codebase of a project using either of the Paragonie polyfills, for PHP cross-version compatibility.


## What's in this repo ?

Two rulesets for PHP_CodeSniffer to check for PHP cross-version compatibility issues in projects, while accounting for polyfills provided by the Paragonie polyfill libraries.

These rulesets prevent false positives from the [PHPCompatibility standard](https://github.com/PHPCompatibility/PHPCompatibility) by excluding back-fills and poly-fills which are provided by those libraries.

Paragonie Polyfill Library | Corresponding PHPCompatibility Ruleset | Includes
--- | --- | ---
[`random_compat`](https://github.com/paragonie/random_compat) | `PHPCompatibilityParagonieRandomCompat`
[`sodium_compat`](https://github.com/paragonie/sodium_compat) | `PHPCompatibilityParagonieSodiumCompat` | `PHPCompatibilityParagonieRandomCompat`

> Note:
> As the `sodium_compat` library has `random_compat` [as a dependency](https://github.com/paragonie/sodium_compat/blob/master/composer.json), the `PHPCompatibilityParagonieSodiumCompat` ruleset includes the `PHPCompatibilityParagonieRandomCompat` ruleset.
>
> In practice, this means that if your project uses both libraries, you just need to use the `PHPCompatibilityParagonieSodiumCompat` ruleset to prevent false positives from both.


## Requirements

* [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer).
    * PHP 5.3+ for use with [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) 2.3.0+.
    * PHP 5.4+ for use with [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) 3.0.2+.

    Use the latest stable release of PHP_CodeSniffer for the best results.
    The minimum _recommended_ version of PHP_CodeSniffer is version 2.6.0.
* [PHPCompatibility](https://github.com/PHPCompatibility/PHPCompatibility) 9.0.0+.


## Installation instructions

The only supported installation method is via [Composer](https://getcomposer.org/).

If you don't have a Composer plugin installed to manage the `installed_paths` setting for PHP_CodeSniffer, run the following from the command-line:
```bash
composer require --dev dealerdirect/phpcodesniffer-composer-installer:^0.4.4 phpcompatibility/phpcompatibility-paragonie:*
composer install
```

If you already have a Composer PHP_CodeSniffer plugin installed, run:
```bash
composer require --dev phpcompatibility/phpcompatibility-paragonie:*
composer install
```

Next, run:
```bash
vendor/bin/phpcs -i
```
If all went well, you will now see that the `PHPCompatibility`, `PHPCompatibilityParagonieRandomCompat` and `PHPCompatibilityParagonieSodiumCompat` standards are installed for PHP_CodeSniffer.


## How to use

Now you can use the following commands to inspect the code in your project for PHP cross-version compatibility:
```bash
./vendor/bin/phpcs -p . --standard=PHPCompatibilityParagonieRandomCompat

./vendor/bin/phpcs -p . --standard=PHPCompatibilityParagonieSodiumCompat
```

By default, you will only receive notifications about deprecated and/or removed PHP features.

To get the most out of the PHPCompatibilityParagonie rulesets, you should specify a `testVersion` to check against. That will enable the checks for both deprecated/removed PHP features as well as the detection of code using new PHP features.

For example:
```bash
# For a project which should be compatible with PHP 5.3 up to and including PHP 7.0:
./vendor/bin/phpcs -p . --standard=PHPCompatibilityParagonieRandomCompat --runtime-set testVersion 5.3-7.0

# For a project which should be compatible with PHP 5.4 and higher:
./vendor/bin/phpcs -p . --standard=PHPCompatibilityParagonieSodiumCompat --runtime-set testVersion 5.4-
```

For more detailed information about setting the `testVersion`, see the README of the generic [PHPCompatibility](https://github.com/PHPCompatibility/PHPCompatibility#sniffing-your-code-for-compatibility-with-specific-php-versions) standard.


### Testing PHP files only

By default PHP_CodeSniffer will analyse PHP, JavaScript and CSS files. As the PHPCompatibility sniffs only target PHP code, you can make the run slightly faster by telling PHP_CodeSniffer to only check PHP files, like so:
```bash
./vendor/bin/phpcs -p . --standard=PHPCompatibilityParagonieRandomCompat --extensions=php --runtime-set testVersion 5.3-
```

## License

All code within the PHPCompatibility organisation is released under the GNU Lesser General Public License (LGPL). For more information, visit https://www.gnu.org/copyleft/lesser.html


## Changelog

### 1.0.1 - 2018-12-16

* Prevent false positives when the ruleset is run over the code of the `RandomCompat` polyfill itself.
* The rulesets are now also tested against PHP 7.3.
    Note: full PHP 7.3 support is only available in combination with PHP_CodeSniffer 2.9.2 or 3.3.1+ due to an incompatibility within PHP_CodeSniffer itself.

### 1.0.0 - 2018-10-07

Initial release of PHPCompatibilityParagonie containing rulesets covering the `random_compat` and `sodium_compat` polyfill libraries.
