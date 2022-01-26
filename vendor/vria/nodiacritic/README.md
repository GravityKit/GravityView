#nodiacritic

[![Build Status](https://travis-ci.org/vria/nodiacritic.svg?branch=master)](https://travis-ci.org/vria/nodiacritic)

Tiny helper function that removes all diacritical signs from characters. Function is aware of German and Danish diacritic particularities.


##Installation

composer.json
```yml
"require": {
    "vria/nodiacritic": "0.*"
}
```

##Use

```php
$noDiacriticString = NoDiacritic::filter("Révolution française");
```

As expected, the result is `Révolution française`.

The library is aware of particularities in German and Danish language:

```php
$noAccentsNiceStreet = NoDiacritic::filter("Schöne straße", "de");
```

The result is `Schoene strasse`.

The code is inspired by WordPress' `remove_accents` function.

You can find the article about the library in [my blog](https://vria.eu/news/2016/4/24/library-and-symfony-bundle-to-remove-diacritic-signs-form-strings).