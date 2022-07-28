<?php

namespace WP_CLI\I18n\Tests;

use WP_CLI\I18n\IterableCodeExtractor;
use WP_CLI\Tests\TestCase;
use WP_CLI\Utils;

class IterableCodeExtractorTest extends TestCase {

	/** @var string A path files are located */
	private static $base;

	public function set_up() {
		parent::set_up();

		/**
		 * PHP5.4 cannot set property with __DIR__ constant.
		 */
		self::$base = Utils\normalize_path( __DIR__ ) . '/data/';

		$property = new \ReflectionProperty( 'WP_CLI\I18n\IterableCodeExtractor', 'dir' );
		$property->setAccessible( true );
		$property->setValue( null, self::$base );
		$property->setAccessible( false );
	}

	public function tear_down() {
		if ( file_exists( self::$base . '/symlinked' ) ) {
			unlink( self::$base . '/symlinked' );
		}

		parent::tear_down();
	}

	public function test_can_include_files() {
		$includes = [ 'foo-plugin', 'bar', 'baz/inc*.js' ];
		$result   = IterableCodeExtractor::getFilesFromDirectory( self::$base, $includes, [], [ 'php', 'js' ] );
		$expected = static::$base . 'foo-plugin/foo-plugin.php';
		$this->assertContains( $expected, $result );
		$expected = static::$base . 'baz/includes/should_be_included.js';
		$this->assertContains( $expected, $result );
		$expected = 'hoge/should_NOT_be_included.js';
		$this->assertNotContains( $expected, $result );
	}

	public function test_can_include_empty_array() {
		$result     = IterableCodeExtractor::getFilesFromDirectory( self::$base, [], [], [ 'php', 'js' ] );
		$expected_1 = static::$base . 'foo-plugin/foo-plugin.php';
		$expected_2 = static::$base . 'baz/includes/should_be_included.js';
		$this->assertContains( $expected_1, $result );
		$this->assertContains( $expected_2, $result );
	}

	public function test_can_include_wildcard() {
		$result     = IterableCodeExtractor::getFilesFromDirectory( self::$base, [ '*' ], [], [ 'php', 'js' ] );
		$expected_1 = static::$base . 'foo-plugin/foo-plugin.php';
		$expected_2 = static::$base . 'baz/includes/should_be_included.js';
		$this->assertContains( $expected_1, $result );
		$this->assertContains( $expected_2, $result );
	}

	public function test_can_include_subdirectories() {
		$result     = IterableCodeExtractor::getFilesFromDirectory( self::$base, [ 'foo/bar/*' ], [], [ 'php', 'js' ] );
		$expected_1 = static::$base . 'foo/bar/foo/bar/foo/bar/deep_directory_also_included.php';
		$expected_2 = static::$base . 'foo/bar/foofoo/included.js';
		$this->assertContains( $expected_1, $result );
		$this->assertContains( $expected_2, $result );
	}

	public function test_can_include_only_php() {
		$result     = IterableCodeExtractor::getFilesFromDirectory( self::$base, [ 'foo/bar/*' ], [], [ 'php' ] );
		$expected_1 = static::$base . 'foo/bar/foo/bar/foo/bar/deep_directory_also_included.php';
		$expected_2 = static::$base . 'foo/bar/foofoo/ignored.js';
		$this->assertContains( $expected_1, $result );
		$this->assertNotContains( $expected_2, $result );
	}

	public function test_can_exclude_override_wildcard() {
		$result     = IterableCodeExtractor::getFilesFromDirectory( self::$base, [ 'foo/bar/*' ], [ 'foo/bar/excluded/*' ], [ 'php' ] );
		$expected_1 = static::$base . 'foo/bar/foo/bar/foo/bar/deep_directory_also_included.php';
		$expected_2 = static::$base . 'foo/bar/excluded/excluded.js';
		$this->assertContains( $expected_1, $result );
		$this->assertNotContains( $expected_2, $result );
	}

	public function test_can_exclude_override_matching_directory() {
		$result     = IterableCodeExtractor::getFilesFromDirectory( self::$base, [ 'foo/bar/*' ], [ 'foo/bar/excluded/*' ], [ 'php' ] );
		$expected_1 = static::$base . 'foo/bar/foo/bar/foo/bar/deep_directory_also_included.php';
		$expected_2 = static::$base . 'foo/bar/excluded/excluded.js';
		$this->assertContains( $expected_1, $result );
		$this->assertNotContains( $expected_2, $result );
	}

	public function test_can_not_exclude_partially_directory() {
		$result     = IterableCodeExtractor::getFilesFromDirectory( self::$base, [ 'foo/bar/*' ], [ 'exc' ], [ 'js' ] );
		$expected_1 = static::$base . 'foo/bar/foo/bar/foo/bar/deep_directory_also_included.php';
		$expected_2 = static::$base . 'foo/bar/excluded/ignored.js';
		$this->assertNotContains( $expected_1, $result );
		$this->assertContains( $expected_2, $result );
	}

	public function test_can_exclude_by_wildcard() {
		$result = IterableCodeExtractor::getFilesFromDirectory( self::$base, [], [ '*' ], [ 'php', 'js' ] );
		$this->assertEmpty( $result );
	}

	public function test_can_exclude_files() {
		$excludes = [ 'hoge' ];
		$result   = IterableCodeExtractor::getFilesFromDirectory( self::$base, [], $excludes, [ 'php', 'js' ] );
		$expected = static::$base . 'hoge/should_NOT_be_included.js';
		$this->assertNotContains( $expected, $result );
	}

	public function test_can_override_exclude_by_include() {
		// Overrides include option
		$includes = [ 'excluded/ignored.js' ];
		$excludes = [ 'excluded/*.js' ];
		$result   = IterableCodeExtractor::getFilesFromDirectory( self::$base, $includes, $excludes, [ 'php', 'js' ] );
		$expected = static::$base . 'foo/bar/excluded/ignored.js';
		$this->assertContains( $expected, $result );
	}

	public function test_can_return_all_directory_files_sorted() {
		$result   = IterableCodeExtractor::getFilesFromDirectory( self::$base, [ '*' ], [], [ 'php', 'blade.php', 'js' ] );
		$expected = array(
			static::$base . 'baz/includes/should_be_included.js',
			static::$base . 'foo-plugin/foo-plugin.php',
			static::$base . 'foo-theme/foo-theme-file.blade.php',
			static::$base . 'foo/bar/excluded/ignored.js',
			static::$base . 'foo/bar/foo/bar/foo/bar/deep_directory_also_included.php',
			static::$base . 'foo/bar/foofoo/included.js',
			static::$base . 'foo/bar/foofoo/minified.min.js',
			static::$base . 'hoge/should_NOT_be_included.js',
			static::$base . 'vendor/vendor-file.php',
			static::$base . 'vendor/vendor1/vendor1-file.php',
		);
		$this->assertEquals( $expected, $result );
	}

	public function test_can_include_file_in_excluded_folder() {
		$includes = [ 'vendor/vendor-file.php' ];
		$excludes = [ 'vendor' ];
		$result   = IterableCodeExtractor::getFilesFromDirectory( self::$base, $includes, $excludes, [ 'php', 'js' ] );
		$expected = static::$base . 'vendor/vendor-file.php';
		$this->assertContains( $expected, $result );
	}

	public function test_can_include_folder_in_excluded_folder() {
		$includes = [ 'vendor/vendor1' ];
		$excludes = [ 'vendor' ];
		$result   = IterableCodeExtractor::getFilesFromDirectory( self::$base, $includes, $excludes, [ 'php', 'js' ] );
		$expected = static::$base . 'vendor/vendor1/vendor1-file.php';
		$this->assertContains( $expected, $result );
	}

	public function test_can_include_file_in_excluded_folder_with_leading_slash() {
		$includes = [ '/vendor/vendor-file.php' ];
		$excludes = [ 'vendor' ];
		$result   = IterableCodeExtractor::getFilesFromDirectory( self::$base, $includes, $excludes, [ 'php', 'js' ] );
		$expected = static::$base . 'vendor/vendor-file.php';
		$this->assertContains( $expected, $result );
	}

	public function test_can_include_file_in_excluded_folder_by_wildcard() {
		$includes = [ 'vendor/**' ];
		$excludes = [ 'vendor' ];
		$result   = IterableCodeExtractor::getFilesFromDirectory( self::$base, $includes, $excludes, [ 'php', 'js' ] );
		$expected = static::$base . 'vendor/vendor-file.php';
		$this->assertContains( $expected, $result );
	}

	public function test_exclude_not_included_files() {
		$includes = [ 'foo/bar/foo/bar/foo/bar/deep_directory_also_included.php' ];
		$result   = IterableCodeExtractor::getFilesFromDirectory( self::$base, $includes, [], [ 'php', 'js' ] );
		$expected = array(
			static::$base . 'foo/bar/foo/bar/foo/bar/deep_directory_also_included.php',
		);
		$this->assertEquals( $expected, $result );
	}

	public function test_wildcard_exclude() {
		$includes = [ 'foofoo/*' ];
		$excludes = [ '*.min.js' ];
		$result   = IterableCodeExtractor::getFilesFromDirectory( self::$base, $includes, $excludes, [ 'php', 'js' ] );
		$expected = array(
			static::$base . 'foo/bar/foofoo/included.js',
		);
		$this->assertEquals( $expected, $result );
	}

	public function test_identical_include_exclude() {
		$includes = [ '*.min.js' ];
		$excludes = [ '*.min.js' ];
		$result   = IterableCodeExtractor::getFilesFromDirectory( self::$base, $includes, $excludes, [ 'php', 'js' ] );
		$expected = array();
		$this->assertEquals( $expected, $result );
	}

	public function test_can_include_file_in_symlinked_folder() {
		symlink( self::$base . '/baz', self::$base . '/symlinked' );
		$includes = [ 'symlinked/includes/should_be_included.js' ];
		$result   = IterableCodeExtractor::getFilesFromDirectory( self::$base, $includes, [], [ 'php', 'js' ] );
		$expected = static::$base . 'symlinked/includes/should_be_included.js';
		$this->assertContains( $expected, $result );
	}

	// IterableCodeExtractor::file_get_extension_multi is a private method
	protected static function get_method_as_public( $class_name, $method_name ) {
		$class  = new \ReflectionClass( $class_name );
		$method = $class->getMethod( $method_name );
		$method->setAccessible( true );
		return $method;
	}

	protected static function file_get_extension_multi_invoke( $file ) {
		$file_get_extension_multi_method = static::get_method_as_public( 'WP_CLI\I18n\IterableCodeExtractor', 'file_get_extension_multi' );
		return $file_get_extension_multi_method->invokeArgs( null, [ $file ] );
	}

	protected static function file_has_file_extension_invoke( $file, $extensions ) {
		$file_get_extension_multi_method = static::get_method_as_public( 'WP_CLI\I18n\IterableCodeExtractor', 'file_has_file_extension' );
		return $file_get_extension_multi_method->invokeArgs( null, [ $file, $extensions ] );
	}

	/**
	 * @dataProvider file_extension_extract_provider
	 */
	public function test_gets_file_extension_correctly( $rel_input_file, $expected_extension ) {
		$this->assertEquals( static::file_get_extension_multi_invoke( new \SplFileObject( self::$base . $rel_input_file ) ), $expected_extension );
	}

	public function file_extension_extract_provider() {
		return [
			[ 'foo/bar/foofoo/included.js', 'js' ],
			[ 'foo-plugin/foo-plugin.php', 'php' ],
			[ 'foo-theme/foo-theme-file.blade.php', 'blade.php' ],
		];
	}

	/**
	 * @dataProvider file_extensions_matches_provider
	 */
	public function test_matches_file_extensions_correctly( $rel_input_file, $matching_extensions, $expected_result ) {
		$this->assertEquals( static::file_has_file_extension_invoke( new \SplFileObject( self::$base . $rel_input_file ), $matching_extensions ), $expected_result );
	}

	public function file_extensions_matches_provider() {
		return [
			[ 'foo/bar/foofoo/included.js', [ 'js' ], true ],
			[ 'foo/bar/foofoo/included.js', [ 'js', 'php', 'blade.php' ], true ],
			[ 'foo/bar/foofoo/included.js', [ 'php' ], false ],
			[ 'foo/bar/foofoo/included.js', [ 'php', 'blade.php' ], false ],

			[ 'foo-plugin/foo-plugin.php', [ 'php', 'js' ], true ],
			[ 'foo-plugin/foo-plugin.php', [ 'php', 'blade.php' ], true ],
			[ 'foo-plugin/foo-plugin.php', [ 'blade.php', 'js' ], false ],
			[ 'foo-plugin/foo-plugin.php', [ 'js', 'blade.php' ], false ],

			[ 'foo-theme/foo-theme-file.blade.php', [ 'php', 'blade.php' ], true ],
			[ 'foo-theme/foo-theme-file.blade.php', [ 'blade.php' ], true ],
			// the last part of a multi file-extension must also match single file-extensions (e.g. `min.js` matches `js`)
			[ 'foo-theme/foo-theme-file.blade.php', [ 'js', 'php' ], true ],
			[ 'foo-theme/foo-theme-file.blade.php', [ 'js' ], false ],
			[ 'foo/bar/foofoo/minified.min.js', [ 'js', 'json', 'php' ], true ],
			[ 'foo/bar/foofoo/minified.min.js', [ 'json', 'php' ], false ],
		];
	}
}
