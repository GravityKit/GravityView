## Upgrade from 1.x to 2.x

The functions previously registered in the global namespace have been
moved to the `Humbug` namespace:

* `humbug_get_contents()` => `Humbug\get_contents()`
* `humbug_set_headers()` => `Humbug\set_headers()`
* `humbug_get_headers()` => `Humbug\get_headers()`
