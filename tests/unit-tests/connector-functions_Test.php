<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group connectorfunctions
 */
class GravityView_Connector_Functions_Test extends GV_UnitTestCase {

	/**
	 * @covers GVCommon::has_gravityview_shortcode()
	 * @covers ::has_gravityview_shortcode()
	 */
	function test_has_gravityview_shortcode() {

		$this->assertFalse( has_gravityview_shortcode( '[gravityview]' ), 'The function should only accept WP_Post object' );

		$no_shortcode = $this->factory->post->create_and_get();
		$shortcode = $this->factory->post->create_and_get(array( 'post_content' => '[gravityview]' ));
		$view = $this->factory->view->create_and_get();

		$this->assertFalse( has_gravityview_shortcode( $no_shortcode ) );
		$this->assertTrue( has_gravityview_shortcode( $shortcode ) );
		$this->assertTrue( has_gravityview_shortcode( $view ) );
	}

	/**
	 * @group shortcode
	 * @see gravityview_has_shortcode_r
	 * @covers GVCommon::has_shortcode_r
	 * @covers ::gravityview_has_shortcode_r()
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
