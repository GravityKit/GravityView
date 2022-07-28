<?php
/**
   * Spyc -- A Simple PHP YAML Class
   * @version 0.6.2
   * @author Vlad Andersen <vlad.andersen@gmail.com>
   * @author Chris Wanstrath <chris@ozmm.org>
   * @link https://github.com/mustangostang/spyc/
   * @copyright Copyright 2005-2006 Chris Wanstrath, 2006-2011 Vlad Andersen
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   * @package Spyc
   */

if (!class_exists('Mustangostang\Spyc')) {
  require_once dirname(__FILE__) . '/src/Spyc.php';
}

class_alias('Mustangostang\Spyc', 'Spyc');

require_once dirname(__FILE__) . '/includes/functions.php';

// Enable use of Spyc from command line
// The syntax is the following: php Spyc.php spyc.yaml
do {
  if (PHP_SAPI != 'cli') break;
  if (empty ($_SERVER['argc']) || $_SERVER['argc'] < 2) break;
  if (empty ($_SERVER['PHP_SELF']) || FALSE === strpos ($_SERVER['PHP_SELF'], 'Spyc.php') ) break;
  $file = $argv[1];
  echo json_encode (spyc_load_file ($file));
} while (0);
