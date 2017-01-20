<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * All future tests live here for now...
 *
 * ...at least until the future Test component appears.
 *
 * @group gvfuture
 */
class GVFuture_Test extends GV_UnitTestCase {
	function setUp() {
		parent::setUp();

		/** The future branch of GravityView requires PHP 5.3+ namespaces. */
		if ( version_compare( phpversion(), '5.3' , '<' ) ) {
			$this->markTestSkipped( 'The future code requires PHP 5.3+' );
			return;
		}

		/** Not being loaded by the plugin yet. */
		if ( ! function_exists( 'gravityview' ) ) {
			$this->markTestSkipped( 'gravityview() is not being loaded by plugin yet' );
			return;
		}
	}

	/**
	 * @covers \GV\Plugin::dir()
	 * @covers \GV\Plugin::url()
	 */
	function test_plugin_dir_and_url() {
		$this->assertEquals( GRAVITYVIEW_DIR, gravityview()->plugin->dir() );
		$this->assertStringEndsWith( '/gravityview/test/this.php', strtolower( gravityview()->plugin->dir( 'test/this.php' ) ) );
		$this->assertStringEndsWith( '/gravityview/and/this.php', strtolower( gravityview()->plugin->dir( '/and/this.php' ) ) );

		/** Due to how WP_PLUGIN_DIR is different in test mode, we are only able to check bits of the URL */
		$this->assertStringStartsWith( 'http', strtolower( gravityview()->plugin->url() ) );
		$this->assertStringEndsWith( '/gravityview/', strtolower( gravityview()->plugin->url() ) );
		$this->assertStringEndsWith( '/gravityview/test/this.php', strtolower( gravityview()->plugin->url( 'test/this.php' ) ) );
		$this->assertStringEndsWith( '/gravityview/and/this.php', strtolower( gravityview()->plugin->url( '/and/this.php' ) ) );
	}

	/**
	 * @covers \GV\Plugin::is_compatible()
	 * @covers \GV\Plugin::is_compatible_wordpress()
	 * @covers \GV\Plugin::is_compatible_gravityforms()
	 * @covers \GV\Plugin::is_compatible_php()
	 */
	function test_plugin_is_compatible() {
		/** Under normal testing conditions this should pass. */
		$this->assertTrue( gravityview()->plugin->is_compatible_php() );
		$this->assertTrue( gravityview()->plugin->is_compatible_wordpress() );
		$this->assertTrue( gravityview()->plugin->is_compatible_gravityforms() );
		$this->assertTrue( gravityview()->plugin->is_compatible() );

		/** Simulate various other conditions, including failure conditions. */
		$GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] = '7.0.99-hhvm';
		$GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] = '4.8-alpha-39901';
		$GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] = '2.1.2.3-alpha';
		$this->assertTrue( gravityview()->plugin->is_compatible_php() );
		$this->assertTrue( gravityview()->plugin->is_compatible_wordpress() );
		$this->assertTrue( gravityview()->plugin->is_compatible_gravityforms() );
		$this->assertTrue( gravityview()->plugin->is_compatible() );

		$GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] = '5.2';
		$GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] = '3.0';
		$GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] = '1.0';
		$this->assertFalse( gravityview()->plugin->is_compatible_php() );
		$this->assertFalse( gravityview()->plugin->is_compatible_wordpress() );
		$this->assertFalse( gravityview()->plugin->is_compatible_gravityforms() );
		$this->assertFalse( gravityview()->plugin->is_compatible() );

		$GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] = '2.1.2.3';
		$GLOBALS['GRAVITYVIEW_TESTS_GF_INACTIVE_OVERRIDE'] = true;
		$this->assertFalse( gravityview()->plugin->is_compatible_gravityforms() );
		$this->assertFalse( gravityview()->plugin->is_compatible() );

		/** Cleanup used overrides. */
		unset( $GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] );
		unset( $GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] );
		unset( $GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] );
		unset( $GLOBALS['GRAVITYVIEW_TESTS_GF_INACTIVE_OVERRIDE'] );

		/** Test deprecations and stubs in the old code. */
		$this->assertTrue( GravityView_Compatibility::is_valid() );
		$this->assertTrue( GravityView_Compatibility::check_php() );
		$this->assertTrue( GravityView_Compatibility::check_wordpress(), 'Failed check for WP version ' . $GLOBALS['wp_version'] );
		$this->assertTrue( GravityView_Compatibility::check_gravityforms() );

		$GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] = '5.2';
		$this->assertFalse( GravityView_Compatibility::is_valid() );
		$this->assertFalse( GravityView_Compatibility::check_php() );
		$GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] = '3.0';
		$this->assertFalse( GravityView_Compatibility::check_wordpress() );
	}
}
