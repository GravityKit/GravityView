<?php

use WP_CLI\Utils;
use WP_CLI\ExitException;
use WP_CLI\Loggers\Execution;
use WP_CLI\Tests\TestCase;

require_once VENDOR_DIR . '/wp-cli/wp-cli/php/utils.php';
require_once VENDOR_DIR . '/wp-cli/wp-cli/php/class-wp-cli.php';
require_once VENDOR_DIR . '/wp-cli/wp-cli/php/class-wp-cli-command.php';

class ComposerJsonTest extends TestCase {

	private $logger            = null;
	private $prev_logger       = null;
	private $prev_capture_exit = null;
	private $temp_dir          = null;

	public function set_up() {
		parent::set_up();

		// Save and set logger.
		$class_wp_cli_logger = new \ReflectionProperty( 'WP_CLI', 'logger' );
		$class_wp_cli_logger->setAccessible( true );
		$this->prev_logger = $class_wp_cli_logger->getValue();

		$this->logger = new Execution();
		WP_CLI::set_logger( $this->logger );

		// Enable exit exception.
		$class_wp_cli_capture_exit = new \ReflectionProperty( 'WP_CLI', 'capture_exit' );
		$class_wp_cli_capture_exit->setAccessible( true );
		$this->prev_capture_exit = $class_wp_cli_capture_exit->getValue();
		$class_wp_cli_capture_exit->setValue( true );

		$this->temp_dir = Utils\get_temp_dir() . uniqid( 'wp-cli-test-package-composer-json-', true ) . '/';
		mkdir( $this->temp_dir );
	}

	public function tear_down() {
		// Restore logger.
		WP_CLI::set_logger( $this->prev_logger );

		// Restore exit exception.
		$class_wp_cli_capture_exit = new \ReflectionProperty( 'WP_CLI', 'capture_exit' );
		$class_wp_cli_capture_exit->setAccessible( true );
		$class_wp_cli_capture_exit->setValue( $this->prev_capture_exit );

		rmdir( $this->temp_dir );

		parent::tear_down();
	}

	public function test_create_default_composer_json() {
		$create_default_composer_json = new \ReflectionMethod( 'Package_Command', 'create_default_composer_json' );
		$create_default_composer_json->setAccessible( true );

		$package = new Package_Command();

		// Fail with bad directory.
		$exception = null;
		try {
			$actual = $create_default_composer_json->invoke( $package, '' );
		} catch ( ExitException $ex ) {
			$exception = $ex;
		}
		$this->assertTrue( null !== $exception );
		$this->assertTrue( 1 === $exception->getCode() );
		$this->assertTrue( empty( $this->logger->stdout ) );
		$this->assertTrue( false !== strpos( $this->logger->stderr, 'Error: Composer directory' ) );

		// Succeed.
		$expected = $this->temp_dir . 'packages/composer.json';
		$actual   = $create_default_composer_json->invoke( $package, $expected );
		$this->assertSame( $expected, $this->mac_safe_path( $actual ) );
		$this->assertTrue( false !== strpos( file_get_contents( $actual ), 'wp-cli/wp-cli' ) );
		unlink( $actual );
		rmdir( dirname( $actual ) );
	}

	public function test_get_composer_json_path() {
		$env_test                = getenv( 'WP_CLI_TEST_PACKAGE_GET_COMPOSER_JSON_PATH' );
		$env_home                = getenv( 'HOME' );
		$env_wp_cli_packages_dir = getenv( 'WP_CLI_PACKAGES_DIR' );

		$get_composer_json_path = new \ReflectionMethod( 'Package_Command', 'get_composer_json_path' );
		$get_composer_json_path->setAccessible( true );

		$package = new Package_Command();

		putenv( 'WP_CLI_TEST_PACKAGE_GET_COMPOSER_JSON_PATH=1' );
		putenv( 'HOME=' . $this->temp_dir );

		// Create in HOME.
		putenv( 'WP_CLI_PACKAGES_DIR' );
		$expected = $this->temp_dir . '.wp-cli/packages/composer.json';
		$actual   = $get_composer_json_path->invoke( $package );
		$this->assertSame( $expected, $this->mac_safe_path( $actual ) );
		$this->assertTrue( false !== strpos( file_get_contents( $actual ), 'wp-cli/wp-cli' ) );
		unlink( $actual );
		rmdir( dirname( $actual ) );
		rmdir( dirname( dirname( $actual ) ) );

		// Create in WP_CLI_PACKAGES_DIR.
		putenv( 'WP_CLI_PACKAGES_DIR=' . $this->temp_dir . 'packages' );
		$expected = $this->temp_dir . 'packages/composer.json';
		$actual   = $get_composer_json_path->invoke( $package );
		$this->assertSame( $expected, $this->mac_safe_path( $actual ) );
		$this->assertTrue( false !== strpos( file_get_contents( $actual ), 'wp-cli/wp-cli' ) );
		unlink( $actual );
		rmdir( dirname( $actual ) );

		// Do nothing as already exists.
		putenv( 'WP_CLI_PACKAGES_DIR=' . $this->temp_dir . 'packages' );
		$expected = $this->temp_dir . 'packages/composer.json';
		mkdir( $this->temp_dir . 'packages' );
		touch( $expected );
		$actual = $get_composer_json_path->invoke( $package );
		$this->assertSame( $expected, $this->mac_safe_path( $actual ) );
		$this->assertSame( 0, filesize( $actual ) );
		unlink( $actual );
		rmdir( dirname( $actual ) );

		putenv( false === $env_test ? 'WP_CLI_TEST_PACKAGE_GET_COMPOSER_JSON_PATH' : "WP_CLI_TEST_PACKAGE_GET_COMPOSER_JSON_PATH=$env_test" );
		putenv( false === $env_home ? 'HOME' : "HOME=$env_home" );
		putenv( false === $env_wp_cli_packages_dir ? 'WP_CLI_PACKAGES_DIR' : "WP_CLI_PACKAGES_DIR=$env_wp_cli_packages_dir" );
	}

	public function test_get_composer_json_path_backup_decoded() {
		$env_test                = getenv( 'WP_CLI_TEST_PACKAGE_GET_COMPOSER_JSON_PATH' );
		$env_wp_cli_packages_dir = getenv( 'WP_CLI_PACKAGES_DIR' );

		putenv( 'WP_CLI_TEST_PACKAGE_GET_COMPOSER_JSON_PATH=1' );
		putenv( 'WP_CLI_PACKAGES_DIR=' . $this->temp_dir . 'packages' );

		$get_composer_json_path_backup_decoded = new \ReflectionMethod( 'Package_Command', 'get_composer_json_path_backup_decoded' );
		$get_composer_json_path_backup_decoded->setAccessible( true );

		$package = new Package_Command();

		// Fail with bad json.
		$expected = $this->temp_dir . 'packages/composer.json';
		mkdir( $this->temp_dir . 'packages' );
		file_put_contents( $expected, '{' );
		$exception = null;
		try {
			$actual = $get_composer_json_path_backup_decoded->invoke( $package );
		} catch ( ExitException $ex ) {
			$exception = $ex;
		}
		$this->assertTrue( null !== $exception );
		$this->assertTrue( 1 === $exception->getCode() );
		$this->assertTrue( empty( $this->logger->stdout ) );
		$this->assertTrue( false !== strpos( $this->logger->stderr, 'Error: Failed to parse' ) );
		unlink( $expected );
		rmdir( dirname( $expected ) );

		// Succeed with newly created.
		$expected                           = $this->temp_dir . 'packages/composer.json';
		list( $actual, $content, $decoded ) = $get_composer_json_path_backup_decoded->invoke( $package );
		$this->assertSame( $expected, $this->mac_safe_path( $actual ) );
		$this->assertTrue( false !== strpos( file_get_contents( $actual ), 'wp-cli/wp-cli' ) );
		$this->assertSame( file_get_contents( $actual ), $content );
		$this->assertFalse( empty( $decoded ) );
		unlink( $expected );
		rmdir( dirname( $expected ) );

		// Succeed with blank.
		$expected = $this->temp_dir . 'packages/composer.json';
		mkdir( $this->temp_dir . 'packages' );
		file_put_contents( $expected, '{}' );
		list( $actual, $content, $decoded ) = $get_composer_json_path_backup_decoded->invoke( $package );
		$this->assertSame( $expected, $this->mac_safe_path( $actual ) );
		$this->assertSame( '{}', $content );
		$this->assertTrue( empty( $decoded ) );
		unlink( $expected );
		rmdir( dirname( $expected ) );

		putenv( false === $env_test ? 'WP_CLI_TEST_PACKAGE_GET_COMPOSER_JSON_PATH' : "WP_CLI_TEST_PACKAGE_GET_COMPOSER_JSON_PATH=$env_test" );
		putenv( false === $env_wp_cli_packages_dir ? 'WP_CLI_PACKAGES_DIR' : "WP_CLI_PACKAGES_DIR=$env_wp_cli_packages_dir" );
	}

	private function mac_safe_path( $path ) {
		return preg_replace( '#^/private/(var|tmp)/#i', '/$1/', $path );
	}
}
