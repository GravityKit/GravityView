<?php

/*
 * This file is part of the Humbug package.
 *
 * (c) 2015 Pádraic Brady <padraic.brady@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!function_exists('humbug_get_contents')) {
    function humbug_get_contents($filename, $use_include_path = false, $context = null)
    {
        @trigger_error(
            'humbug_get_contents() is deprecated since 1.1.0 and will be removed in 2.0.0. Use '
            .'Humbug/get_contents() instead.',
            E_USER_DEPRECATED
        );

        return Humbug\get_contents($filename, $use_include_path, $context);
    }
}

if (!function_exists('humbug_get_headers')) {
    function humbug_get_headers()
    {
        @trigger_error(
            'humbug_get_headers() is deprecated since 1.1.0 and will be removed in 2.0.0. Use '
            .'Humbug/get_headers() instead.',
            E_USER_DEPRECATED
        );

        return Humbug\get_headers();
    }
}

if (!function_exists('humbug_set_headers')) {
    function humbug_set_headers(array $headers)
    {
        @trigger_error(
            'humbug_set_headers() is deprecated since 1.1.0 and will be removed in 2.0.0. Use '
            .'Humbug/get_headers() instead.',
            E_USER_DEPRECATED
        );

        Humbug\set_headers($headers);
    }
}
