<?php
/**
 * @license MIT
 *
 * Modified by gravityview on 23-December-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

use GravityKit\GravityView\Symfony\Component\HttpFoundation\Cookie;

$r = require __DIR__.'/common.inc';

$str = '?*():@&+$/%#[]';

$r->headers->setCookie(new Cookie($str, $str, 0, '/', null, false, false, true));
$r->sendHeaders();

setrawcookie($str, $str, 0, '/', null, false, false);
