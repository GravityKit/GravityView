<?php
/**
 * @license MIT
 *
 * Modified by gravityview on 07-November-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

spl_autoload_register(
    function ($class) {
        if (strpos($class, 'GravityKit\\GravityView\\Gettext\\Languages\\') !== 0) {
            return;
        }
        $file = __DIR__ . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen('GravityKit\\GravityView\\Gettext\\Languages'))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
);
