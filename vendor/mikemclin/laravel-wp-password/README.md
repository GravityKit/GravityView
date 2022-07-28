Laravel WP Password
===================

[![Build Status](https://img.shields.io/travis/mikemclin/laravel-wp-password/master.svg?style=flat-square)](https://travis-ci.org/mikemclin/laravel-wp-password)
[![Coverage Status](https://img.shields.io/coveralls/mikemclin/laravel-wp-password/master.svg?style=flat-square)](https://coveralls.io/r/mikemclin/laravel-wp-password?branch=master)

This Laravel 4/5/6/7 package provides an easy way to create and check against WordPress password hashes. WordPress is not required.


Installation
------------

#### Step 1: Composer

Begin by installing this package through Composer. Edit your project's `composer.json` file to require `mikemclin/laravel-wp-password`.

```json
"require": {
  "mikemclin/laravel-wp-password": "~2.0.1"
}
```

Next, update Composer from the Terminal:

```shell
composer update
```

#### Step 2: Register Laravel Service Provider

Once this operation completes, the final step is to add the service provider.

* **Laravel 5.x**: Open `config/app.php`, and add a new item to the providers array
* **Laravel 4.x**: Open `app/config/app.php`, and add a new item to the providers array

```php
'MikeMcLin\WpPassword\WpPasswordProvider'
```


Usage
-----

Add a **use statement** for the WpPassword facade

```php
use MikeMcLin\WpPassword\Facades\WpPassword;
```

### `make()` - Create Password Hash

Similar to the WordPress [`wp_hash_password()`](http://codex.wordpress.org/Function_Reference/wp_hash_password) function

```php
$hashed_password = WpPassword::make('plain-text-password');
```

### `check()` - Check Password Hash

Similar to the WordPress [`wp_check_password()`](http://codex.wordpress.org/Function_Reference/wp_check_password) function

```php
$password = 'plain-text-password';
$wp_hashed_password = '$P$B7TRc6vrwCfjgKLZLgmN.dmPo6msZR.';

if ( WpPassword::check($password, $wp_hashed_password) ) {
    // Password success!
} else {
    // Password failed :(
}
```

### Dependency Injection

I used a facade above to simplify the documentation.  If you'd prefer not to use the facade, you can inject the following interface: `MikeMcLin\WpPassword\Contracts\WpPassword`.
