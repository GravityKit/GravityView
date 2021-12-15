<?php


namespace WP_CLI\I18n\Tests;

use WP_CLI\I18n\MakeJsonCommand;
use WP_CLI\Tests\TestCase;
use WP_CLI\Utils;

class MakeJsonMapTest extends TestCase {
	/** @var string A path files are located */
	private static $base;
	/** @var \ReflectionMethod build_map reflection (private) */
	private static $build_map = null;
	/** @var MakeJsonCommand instance */
	private static $obj = null;

	public function set_up() {
		parent::set_up();

		/**
		 * PHP5.4 cannot set property with __DIR__ constant.
		 * Shamelessly stolen from @see IterableCodeExtractorTest.php
		 */
		self::$base = Utils\normalize_path( __DIR__ ) . '/data/';

		self::$obj       = new MakeJsonCommand();
		$reflection      = new \ReflectionClass( get_class( self::$obj ) );
		self::$build_map = $reflection->getMethod( 'build_map' );
		self::$build_map->setAccessible( true );
	}

	public function test_no_map() {
		$result   = self::$build_map->invoke( self::$obj, false );
		$expected = null;
		$this->assertEquals( $expected, $result );
	}

	public function test_invalid_map() {
		$maps     = self::$base . 'maps/invalid.json';
		$result   = self::$build_map->invoke( self::$obj, $maps );
		$expected = [];
		$this->assertEquals( $expected, $result );
	}

	public function test_basic_map() {
		$maps     = self::$base . 'maps/basic.json';
		$result   = self::$build_map->invoke( self::$obj, $maps );
		$expected = [
			'src/index.js'   => [ 'dist/index.js' ],
			'src/include.js' => [ 'dist/index.js' ],
		];
		$this->assertEquals( $expected, $result );
	}

	public function test_mixed_map() {
		$maps     = self::$base . 'maps/mixed.json';
		$result   = self::$build_map->invoke( self::$obj, $maps );
		$expected = [
			'src/index.js' => [ 'dist/index.js' ],
			'src/other.js' => [ 'dist/index.js' ],
		];
		$this->assertEquals( $expected, $result );
	}

	public function test_other_map() {
		$maps     = self::$base . 'maps/other.json';
		$result   = self::$build_map->invoke( self::$obj, $maps );
		$expected = [
			'src/index.js'   => [ 'dist/index.js' ],
			'src/include.js' => [ 'dist/index.js', 'dist/other.js' ],
		];
		$this->assertEquals( $expected, $result );
	}

	public function test_invalid_values_map() {
		$maps     = self::$base . 'maps/invalid_values.json';
		$result   = self::$build_map->invoke( self::$obj, $maps );
		$expected = [
			'src/index.js' => null,
			'src/other.js' => null,
			'src/valid.js' => [ 'string' ],
		];
		$this->assertEquals( $expected, $result );
	}

	public function test_merge_map() {
		$maps     = [ self::$base . 'maps/basic.json', self::$base . 'maps/mixed.json' ];
		$result   = self::$build_map->invoke( self::$obj, $maps );
		$expected = [
			// double is expected because it's in both files. no use bothering to remove it, that's done in make_json anyways
			'src/index.js'   => [ 'dist/index.js', 'dist/index.js' ],
			'src/other.js'   => [ 'dist/index.js' ],
			'src/include.js' => [ 'dist/index.js' ],
		];
		$this->assertEquals( $expected, $result );
	}

	public function test_merge_same_map() {
		$maps     = [ self::$base . 'maps/basic.json', self::$base . 'maps/mixed.json', self::$base . 'maps/other.json' ];
		$result   = self::$build_map->invoke( self::$obj, $maps );
		$expected = [
			'src/index.js'   => [ 'dist/index.js', 'dist/index.js', 'dist/index.js' ],
			'src/other.js'   => [ 'dist/index.js' ],
			'src/include.js' => [ 'dist/index.js', 'dist/index.js', 'dist/other.js' ],
		];
		$this->assertEquals( $expected, $result );
	}

	public function test_merge_invalid_values_map() {
		// merge both ways
		$maps     = [ self::$base . 'maps/basic.json', self::$base . 'maps/invalid_values.json' ];
		$result   = self::$build_map->invoke( self::$obj, $maps );
		$expected = [
			'src/index.js'   => [ 'dist/index.js', null ],
			'src/other.js'   => null,
			'src/valid.js'   => [ 'string' ],
			'src/include.js' => [ 'dist/index.js' ],
		];
		$this->assertEquals( $expected, $result );

		$maps     = [ self::$base . 'maps/invalid_values.json', self::$base . 'maps/basic.json' ];
		$result   = self::$build_map->invoke( self::$obj, $maps );
		$expected = [
			'src/index.js'   => [ null, 'dist/index.js' ],
			'src/other.js'   => null,
			'src/valid.js'   => [ 'string' ],
			'src/include.js' => [ 'dist/index.js' ],
		];
		$this->assertEquals( $expected, $result );
	}
}
