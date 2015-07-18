<?php

class GravityView_Connector_Functions_Test extends PHPUnit_Framework_TestCase {

	function setUp() {
		parent::setUp();

		GravityView_Plugin::getInstance();
	}

	/**
	 * @group shortcode
	 * @covers gravityview_has_shortcode_r()
	 */
	function test_gravityview_has_shortcode_r() {

        $single_view = array(
            '[gravityview id=123]',
            null,
            'gravityview',
            ' id=123',
            null,
            null,
            null,
        );

		$shortcodes = gravityview_has_shortcode_r('[gravityview id=123]');

		$this->assertEquals( $shortcodes, array( $single_view ), 'failed unnested test');

		$shortcodes = gravityview_has_shortcode_r('[gravityview id=123][gravityview id=123]');

		$this->assertEquals( $shortcodes, array( $single_view, $single_view ), 'unnested two shortcodes test');

		$shortcodes = gravityview_has_shortcode_r('[example][gravityview id=123][example2][gravityview id=123][example3][gravityview id=123][/example3][/example2][/example]');

		$this->assertEquals( $shortcodes, array( $single_view, $single_view, $single_view ), 'nested three shortcodes' );

	}
}
