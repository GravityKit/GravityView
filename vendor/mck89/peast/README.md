Peast
==========

[![Latest Stable Version](https://poser.pugx.org/mck89/peast/v/stable)](https://packagist.org/packages/mck89/peast)
[![Total Downloads](https://poser.pugx.org/mck89/peast/downloads)](https://packagist.org/packages/mck89/peast)
[![License](https://poser.pugx.org/mck89/peast/license)](https://packagist.org/packages/mck89/peast)
[![Build Status](https://travis-ci.org/mck89/peast.svg?branch=master)](https://travis-ci.org/mck89/peast)


**Peast** _(PHP ECMAScript Abstract Syntax Tree)_ is a PHP 5.4+ library that parses JavaScript code, according to [ECMAScript specification](http://www.ecma-international.org/publications/standards/Ecma-262.htm), and generates an abstract syntax tree following the [ESTree standard](https://github.com/estree/estree).

Installation
-------------
Include the following requirement to your composer.json:
```
{
	"require": {
		"mck89/peast": "dev-master"
	}
}
```

Run `composer install` to install the package.

Then in your script include the autoloader and you can start using Peast:

```php
require_once "vendor/autoload.php";

$source = "var a = 1"; //Your JavaScript code
$ast = Peast\Peast::latest($source, $options)->parse(); //Parse it!
```

Documentation
-------------
Read the documentation for more examples and explanations:

 1. [AST generation and tokenization](doc/ast-and-tokenization.md)
 2. [Tree Traversing](doc/tree-traversing.md)
 3. [Querying By Selector](doc/querying-by-selector.md)
 4. [Rendering](doc/rendering.md)

[Changelog](doc/changelog.md)
