<?php

return [
	'output'        => [
		'runtime' => '=<path>',
		'file'    => '<path>',
		'desc'    => 'Path to output file',
	],

	'version'       => [
		'runtime' => '=<version>',
		'file'    => '<version>',
		'desc'    => 'New package version',
	],

	'store-version' => [
		'runtime' => '',
		'file'    => '<bool>',
		'default' => false,
		'desc'    => 'If true the contents of ./VERSION will be set to the value passed to --version',
	],

	'quiet'         => [
		'runtime' => '',
		'file'    => '<bool>',
		'default' => false,
		'desc'    => 'Suppress informational messages',
	],

	'build'         => [
		'runtime' => '=<cli>',
		'file'    => '<cli>',
		'default' => '',
		'desc'    => 'Create a minimum test build "cli", that only supports cli commands',
	],
];

