<?php

ob_start();

//change this to your path
$path = '/Users/zackkatz/Sites/wordpress-develop/tests/phpunit/includes/bootstrap.php';

if (file_exists($path)) {
    $GLOBALS['wp_tests_options'] = array(
        'active_plugins' => array(
        	'gravityforms/gravityforms.php',
        	'gravityview/gravityview.php'
        )
    );

    require_once $path;
} else {
    exit("Couldn't find wordpress-tests/bootstrap.php\n");
}

require_once dirname( __FILE__ ) . '/../gravityview.php';
require_once dirname( __FILE__ ) . '/../../gravityforms/gravityforms.php';
