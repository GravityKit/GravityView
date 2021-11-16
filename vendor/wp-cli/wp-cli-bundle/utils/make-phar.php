<?php

define( 'WP_CLI_BUNDLE_ROOT', rtrim( dirname( dirname( __FILE__ ) ), '/' ) );

if ( file_exists( WP_CLI_BUNDLE_ROOT . '/vendor/autoload.php' ) ) {
	define( 'WP_CLI_BASE_PATH', WP_CLI_BUNDLE_ROOT );
	define( 'WP_CLI_VENDOR_DIR', WP_CLI_BUNDLE_ROOT . '/vendor' );
} elseif ( file_exists( dirname( dirname( WP_CLI_BUNDLE_ROOT ) ) . '/autoload.php' ) ) {
	define( 'WP_CLI_BASE_PATH', dirname( dirname( dirname( WP_CLI_BUNDLE_ROOT ) ) ) );
	define( 'WP_CLI_VENDOR_DIR', dirname( dirname( WP_CLI_BUNDLE_ROOT ) ) );
} else {
	fwrite( STDERR, 'Missing vendor/autoload.php' . PHP_EOL );
	exit( 1 );
}

define( 'WP_CLI_ROOT', rtrim( WP_CLI_VENDOR_DIR, '/' ) . '/wp-cli/wp-cli' );

require WP_CLI_VENDOR_DIR . '/autoload.php';
require WP_CLI_ROOT . '/php/utils.php';

use Symfony\Component\Finder\Finder;
use WP_CLI\Utils;
use WP_CLI\Configurator;

$configurator = new Configurator( WP_CLI_BUNDLE_ROOT . '/utils/make-phar-spec.php' );

list( $args, $assoc_args, $runtime_config ) = $configurator->parse_args( array_slice( $GLOBALS['argv'], 1 ) );

if ( ! isset( $args[0] ) || empty( $args[0] ) ) {
	fwrite( STDERR, "usage: php -dphar.readonly=0 $argv[0] <path> [--quiet] [--version=same|patch|minor|major|x.y.z] [--store-version] [--build=cli]" . PHP_EOL );
	exit( 1 );
}

define( 'DEST_PATH', $args[0] );

define( 'BE_QUIET', isset( $runtime_config['quiet'] ) && $runtime_config['quiet'] );

define( 'BUILD', isset( $runtime_config['build'] ) ? $runtime_config['build'] : '' );

$current_version = trim( file_get_contents( WP_CLI_ROOT . '/VERSION' ) );

if ( isset( $runtime_config['version'] ) ) {
	$new_version = $runtime_config['version'];
	$new_version = Utils\increment_version( $current_version, $new_version );

	if ( isset( $runtime_config['store-version'] ) && $runtime_config['store-version'] ) {
		file_put_contents( WP_CLI_ROOT . '/VERSION', $new_version );
	}

	$current_version = $new_version;
}

function add_file( $phar, $path ) {
	$key = str_replace( WP_CLI_BASE_PATH, '', $path );

	if ( ! BE_QUIET ) {
		echo "$key - $path" . PHP_EOL;
	}

	$basename = basename( $path );
	if ( 0 === strpos( $basename, 'autoload_' ) && preg_match( '/(?:classmap|files|namespaces|psr4|static)\.php$/', $basename ) ) {
		// Strip autoload maps of unused stuff.
		static $strip_res = null;
		if ( null === $strip_res ) {
			if ( 'cli' === BUILD ) {
				$strips = [
					'\/(?:behat|composer|gherkin)\/src\/',
					'\/behat\/',
					'\/phpunit\/',
					'\/phpspec\/',
					'\/sebastian\/',
					'\/php-parallel-lint\/',
					'\/nb\/oxymel\/',
					'-command\/src\/',
					'\/wp-cli\/[^\n]+?-command\/',
					'\/symfony\/(?:config|console|debug|dependency-injection|event-dispatcher|filesystem|translation|yaml)',
					'\/(?:dealerdirect|myclabs|squizlabs|wimg)\/',
					'\/yoast\/',
				];
			} else {
				$strips = [
					'\/(?:behat|gherkin)\/src\/',
					'\/behat\/',
					'\/phpunit\/',
					'\/phpspec\/',
					'\/sebastian\/',
					'\/php-parallel-lint\/',
					'\/symfony\/(?:config|debug|dependency-injection|event-dispatcher|translation|yaml)',
					'\/composer\/spdx-licenses\/',
					'\/Composer\/(?:Command\/|Compiler\.php|Console\/|Downloader\/Pear|Installer\/Pear|Question\/|Repository\/Pear|SelfUpdate\/)',
					'\/(?:dealerdirect|myclabs|squizlabs|wimg)\/',
					'\/yoast\/',
				];
			}
			$strip_res = array_map(
				static function ( $v ) {
						return '/^[^,\n]+?' . $v . '[^,\n]+?, *\n/m';
				},
				$strips
			);
		}
		$phar[ $key ] = preg_replace( $strip_res, '', file_get_contents( $path ) );
	} else {
		$phar[ $key ] = file_get_contents( $path );
	}
}

function set_file_contents( $phar, $path, $content ) {
	$key = str_replace( WP_CLI_BASE_PATH, '', $path );

	if ( ! BE_QUIET ) {
		echo "$key - $path" . PHP_EOL;
	}

	$phar[ $key ] = $content;
}

function get_composer_versions( $current_version ) {
	$composer_lock_path = WP_CLI_BUNDLE_ROOT . '/composer.lock';
	$composer_lock_file = file_get_contents( $composer_lock_path );
	if ( ! $composer_lock_file ) {
		fwrite( STDERR, sprintf( "Warning: Failed to read '%s'." . PHP_EOL, $composer_lock_path ) );
		return '';
	}

	$composer_lock = json_decode( $composer_lock_file, true );
	if ( ! $composer_lock ) {
		fwrite( STDERR, sprintf( "Warning: Could not decode '%s'." . PHP_EOL, $composer_lock_path ) );
		return '';
	}

	if ( ! isset( $composer_lock['packages'] ) ) {
		fwrite( STDERR, sprintf( "Warning: No packages in '%s'." . PHP_EOL, $composer_lock_path ) );
		return '';
	}

	$vendor_versions    = [ implode( ' ', [ 'wp-cli/wp-cli', $current_version, gmdate( 'c' ) ] ) ];
	$missing_names      = 0;
	$missing_versions   = 0;
	$missing_references = 0;
	foreach ( $composer_lock['packages'] as $package ) {
		if ( isset( $package['name'] ) ) {
			$vendor_version = [ $package['name'] ];
			if ( isset( $package['version'] ) ) {
				$vendor_version[] = $package['version'];
			} else {
				$vendor_version[] = 'unknown_version';
				$missing_versions++;
			}
			if ( isset( $package['source'] ) && isset( $package['source']['reference'] ) ) {
				$vendor_version[] = $package['source']['reference'];
			} elseif ( isset( $package['dist'] ) && isset( $package['dist']['reference'] ) ) {
				$vendor_version[] = $package['dist']['reference'];
			} else {
				$vendor_version[] = 'unknown_reference';
				$missing_references++;
			}
			$vendor_versions[] = implode( ' ', $vendor_version );
		} else {
			$vendor_versions[] = implode( ' ', [ 'unknown_package', 'unknown_version', 'unknown_reference' ] );
			$missing_names++;
		}
	}
	if ( $missing_names ) {
		fwrite( STDERR, sprintf( "Warning: %d package names missing from '%s'." . PHP_EOL, $missing_names, $composer_lock_path ) );
	}
	if ( $missing_versions ) {
		fwrite( STDERR, sprintf( "Warning: %d package versions missing from '%s'." . PHP_EOL, $missing_versions, $composer_lock_path ) );
	}
	if ( $missing_references ) {
		fwrite( STDERR, sprintf( "Warning: %d package references missing from '%s'." . PHP_EOL, $missing_references, $composer_lock_path ) );
	}
	return implode( "\n", $vendor_versions );
}

if ( file_exists( DEST_PATH ) ) {
	unlink( DEST_PATH );
}
$phar = new Phar( DEST_PATH, 0, 'wp-cli.phar' );

$phar->startBuffering();

// PHP files
$finder = new Finder();
$finder
	->files()
	->ignoreVCS( true )
	->name( '/\.*.php8?/' )
	->in( WP_CLI_ROOT . '/php' )
	->in( WP_CLI_BUNDLE_ROOT . '/php' )
	->in( WP_CLI_VENDOR_DIR . '/mustache' )
	->in( WP_CLI_VENDOR_DIR . '/rmccue/requests' )
	->in( WP_CLI_VENDOR_DIR . '/composer' )
	->in( WP_CLI_VENDOR_DIR . '/symfony' )
	->notName( 'behat-tags.php' )
	->notPath( '#(?:[^/]+-command|php-cli-tools)/vendor/#' ) // For running locally, in case have composer installed or symlinked them.
	->exclude( 'config' )
	->exclude( 'debug' )
	->exclude( 'dependency-injection' )
	->exclude( 'event-dispatcher' )
	->exclude( 'translation' )
	->exclude( 'yaml' )
	->exclude( 'examples' )
	->exclude( 'features' )
	->exclude( 'test' )
	->exclude( 'tests' )
	->exclude( 'Test' )
	->exclude( 'Tests' );
if ( is_dir( WP_CLI_VENDOR_DIR . '/react' ) ) {
	$finder
		->in( WP_CLI_VENDOR_DIR . '/react' );
}
if ( 'cli' === BUILD ) {
	$finder
		->in( WP_CLI_VENDOR_DIR . '/wp-cli/mustangostang-spyc' )
		->in( WP_CLI_VENDOR_DIR . '/wp-cli/php-cli-tools' )
		->in( WP_CLI_VENDOR_DIR . '/seld/cli-prompt' )
		->exclude( 'console' )
		->exclude( 'filesystem' )
		->exclude( 'composer/ca-bundle' )
		->exclude( 'composer/semver' )
		->exclude( 'composer/src' )
		->exclude( 'composer/spdx-licenses' );
} else {
	$finder
		->in( WP_CLI_VENDOR_DIR . '/wp-cli' )
		->in( WP_CLI_VENDOR_DIR . '/nb/oxymel' )
		->in( WP_CLI_VENDOR_DIR . '/psr' )
		->in( WP_CLI_VENDOR_DIR . '/seld' )
		->in( WP_CLI_VENDOR_DIR . '/justinrainbow/json-schema' )
		->in( WP_CLI_VENDOR_DIR . '/gettext' )
		->in( WP_CLI_VENDOR_DIR . '/mck89' )
		->exclude( 'demo' )
		->exclude( 'nb/oxymel/OxymelTest.php' )
		->exclude( 'composer/spdx-licenses' )
		->exclude( 'composer/composer/src/Composer/Command' )
		->exclude( 'composer/composer/src/Composer/Compiler.php' )
		->exclude( 'composer/composer/src/Composer/Console' )
		->exclude( 'composer/composer/src/Composer/Downloader/PearPackageExtractor.php' ) // Assuming Pear installation isn't supported by wp-cli.
		->exclude( 'composer/composer/src/Composer/Installer/PearBinaryInstaller.php' )
		->exclude( 'composer/composer/src/Composer/Installer/PearInstaller.php' )
		->exclude( 'composer/composer/src/Composer/Question' )
		->exclude( 'composer/composer/src/Composer/Repository/Pear' )
		->exclude( 'composer/composer/src/Composer/SelfUpdate' );
}

foreach ( $finder as $file ) {
	add_file( $phar, $file );
}

// other files
$finder = new Finder();
$finder
	->files()
	->ignoreVCS( true )
	->ignoreDotFiles( false )
	->in( WP_CLI_ROOT . '/templates' )
	->in( WP_CLI_VENDOR_DIR . '/wp-cli/*-command/templates' );

foreach ( $finder as $file ) {
	add_file( $phar, $file );
}

if ( 'cli' !== BUILD ) {
	// Include base project files, because the autoloader will load them
	if ( WP_CLI_BASE_PATH !== WP_CLI_BUNDLE_ROOT && is_dir( WP_CLI_BASE_PATH . '/src' ) ) {
		$finder = new Finder();
		$finder
			->files()
			->ignoreVCS( true )
			->name( '*.php' )
			->in( WP_CLI_BASE_PATH . '/src' )
			->exclude( 'test' )
			->exclude( 'tests' )
			->exclude( 'Test' )
			->exclude( 'Tests' );
		foreach ( $finder as $file ) {
			add_file( $phar, $file );
		}
		// Any PHP files in the project root
		foreach ( glob( WP_CLI_BASE_PATH . '/*.php' ) as $file ) {
			add_file( $phar, $file );
		}
	}
}

add_file( $phar, WP_CLI_VENDOR_DIR . '/autoload.php' );
if ( 'cli' !== BUILD ) {
	add_file( $phar, WP_CLI_VENDOR_DIR . '/composer/composer/LICENSE' );
	add_file( $phar, WP_CLI_VENDOR_DIR . '/composer/composer/res/composer-schema.json' );
}
add_file( $phar, WP_CLI_VENDOR_DIR . '/rmccue/requests/library/Requests/Transport/cacert.pem' );

set_file_contents( $phar, WP_CLI_ROOT . '/COMPOSER_VERSIONS', get_composer_versions( $current_version ) );
set_file_contents( $phar, WP_CLI_ROOT . '/VERSION', $current_version );

$phar_boot = str_replace( WP_CLI_BASE_PATH, '', WP_CLI_BUNDLE_ROOT . '/php/boot-phar.php' );
$phar_boot = '/' . ltrim( $phar_boot, '/' );
$phar->setStub(
	<<<EOB
#!/usr/bin/env php
<?php
Phar::mapPhar();
include 'phar://wp-cli.phar{$phar_boot}';
__HALT_COMPILER();
?>
EOB
);

$phar->stopBuffering();

chmod( DEST_PATH, 0755 ); // Make executable.

if ( ! BE_QUIET ) {
	echo 'Generated ' . DEST_PATH . PHP_EOL;
}
