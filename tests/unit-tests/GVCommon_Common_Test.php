<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group gvcommon
 */
class GVCommon_Test extends GV_UnitTestCase {


	/**
	 * @covers GVCommon::has_gravityview_shortcode()
	 */
	function test_has_gravityview_shortcode() {

		$post_without_shortcode = $this->factory->post->create_and_get(array('post_content' => '[gravityview is not a shortcode'));
		$post_with_shortcode = $this->factory->post->create_and_get(array('post_content' => '[gravityview]'));
		$gravityview_post = $this->factory->view->create_and_get();

		$this->assertTrue( GVCommon::has_gravityview_shortcode( $gravityview_post ) );
		$this->assertTrue( GVCommon::has_gravityview_shortcode( $post_with_shortcode ) );
		$this->assertFalse( GVCommon::has_gravityview_shortcode( $post_without_shortcode ) );
	}

	/**
	 * @covers GVCommon::get_connected_views
	 * @covers ::gravityview_get_connected_views()
	 */
	function test_get_connected_views() {

		$form_id = $this->factory->form->create();

		$this->factory->view->create_many( 20, array( 'form_id' => $form_id ) );

		$views = GVCommon::get_connected_views( $form_id );

		$this->assertEquals( 20, sizeof( $views ) );
	}

	/**
	 * @covers GVCommon::get_meta_form_id
	 * @covers ::gravityview_get_form_id()
	 */
	function test_get_meta_form_id() {

		$form_id = '1234';
		$view_id = $this->factory->view->create( array( 'form_id' => $form_id ) );

		$this->assertEquals( $form_id, GVCommon::get_meta_form_id( $view_id ) );
	}

	/**
	 * @covers GVCommon::get_meta_template_id
	 * @covers ::gravityview_get_template_id()
	 */
	function test_get_meta_template_id() {

		$form_id = '1234';
		$template_id = 'example_template_id';
		$view_id = $this->factory->view->create( array(
			'form_id' => $form_id,
			'template_id' => $template_id,
		) );

		$this->assertEquals( $template_id, GVCommon::get_meta_template_id( $view_id ) );
	}

	/**
	 * @group link_html
	 * @covers ::gravityview_get_link()
	 * @covers GVCommon::get_link_html
	 */
	function test_get_link_html() {

		$this->assertEquals( '<a href="#">Basic</a>', GVCommon::get_link_html( '#', 'Basic' ) );
		$this->assertEquals( '<a title="New Title" href="#">Basic</a>', GVCommon::get_link_html( '#', 'Basic', array( 'title' => 'New Title' ) ) );
		$this->assertEquals( '<a title="New Title" href="#">Basic</a>', GVCommon::get_link_html( '#', 'Basic', array( 'title' => 'New Title' ) ) );
		$this->assertEquals( '<a onclick="alert(&quot;Javascript!&quot;);" href="#">Basic</a>', GVCommon::get_link_html( '#', 'Basic', array( 'onclick' => 'alert("Javascript!");' ) ) );

		// Make sure running esc_url_raw
		$href = '//?dangerous=alert("example");&quot;%20;';
		$this->assertEquals( '<a href="'.esc_url_raw( $href ).'">Basic</a>', GVCommon::get_link_html( $href, 'Basic' ) );

		// Test gravityview/get_link/allowed_atts filter
		add_filter( 'gravityview/get_link/allowed_atts', array( $this, '_filter_test_get_link_html' ) );
		$this->assertEquals( '<a href="#">Basic</a>', GVCommon::get_link_html( '#', 'Basic', array( 'onclick' => 'alert("Javascript!");' ) ) );
		remove_filter( '', array( $this, '_filter_test_get_link_html' ) );
	}

	public function _filter_test_get_link_html( $allowed_atts ) {
		unset( $allowed_atts['onclick'] );
		return $allowed_atts;
	}

	/**
	 * @covers GVCommon::has_shortcode_r
	 */
	function test_has_shortcode_r() {

		add_shortcode( 'shortcode_one', '__return_empty_string' );
		add_shortcode( 'shortcode_two', '__return_empty_string' );

		$shortcode_exists = array(
			'[gravityview]',
			'[shortcode_one][shortcode_two][gravityview][/shortcode_two][/shortcode_one]',
			'[shortcode_one] [shortcode_two] [gravityview /] [/shortcode_two] [/shortcode_one]',
			'[shortcode_one][gravityview][/shortcode_one]',
			'[shortcode_one]

			[shortcode_two]

			[gravityview /]

			[/shortcode_two]

			[/shortcode_one]',
		);

		foreach ( $shortcode_exists as $item ) {
			$this->assertNotEmpty( GVCommon::has_shortcode_r( $item ) );
		}

		$should_be_false = array(
			'[gravity_view]',
			'gravityview',
			'[gravityview',
			'[gravity view]',
		);

		foreach ( $should_be_false as $item ) {
			$this->assertFalse( GVCommon::has_shortcode_r( $item ) );
		}
	}

}
