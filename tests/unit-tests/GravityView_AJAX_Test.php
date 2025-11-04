<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

class GravityView_Ajax_Test extends GV_UnitTestCase {

	/**
	 * @var GravityView_Ajax
	 */
	var $AJAX;

	/**
	 * @var GravityView_Preset_Business_Data
	 */
	var $GravityView_Preset_Business_Data;

	function setUp() : void {

		parent::setUp();

		$this->AJAX = new GravityView_Ajax;
		$this->create_test_nonce();
		$this->GravityView_Preset_Business_Data = new GravityView_Preset_Business_Data;

		require_once( GFCommon::get_base_path() . '/export.php' );
	}

	/**
	 * Set a valid "gravityview_ajaxviews" $_POST['nonce'] value
	 * @see GravityView_Ajax::check_ajax_nonce()
	 */
	function create_test_nonce() {
		$_POST['nonce'] = wp_create_nonce( 'gravityview_ajaxviews' );
	}

	/**
	 * @covers GravityView_Ajax::pre_get_form_fields()
	 * @group gvajax
	 * @covers GravityView_Ajax::get_available_fields_html
	 */
	function test_get_available_fields_html() {

		$_POST['template_id'] = $this->GravityView_Preset_Business_Data->template_id;

		// Test form generation and default context
		add_action( 'gravityview_render_available_fields', array( $this, 'get_available_fields_html_DEFAULT' ), 10, 2 );
		$this->AJAX->get_available_fields_html();
		remove_action( 'gravityview_render_available_fields', array( $this, 'get_available_fields_html_DEFAULT' ), 10 );

		// Test SINGLE context being set
		$_POST['context'] = 'single';
		add_action( 'gravityview_render_available_fields', array( $this, 'get_available_fields_html_SINGLE_CONTEXT' ), 10, 2 );
		$this->AJAX->get_available_fields_html();
		remove_action( 'gravityview_render_available_fields', array( $this, 'get_available_fields_html_SINGLE_CONTEXT' ), 10 );

		// Test EDIT context being set
		$_POST['context'] = 'edit';
		add_action( 'gravityview_render_available_fields', array( $this, 'get_available_fields_html_EDIT_CONTEXT' ), 10, 2 );
		$this->AJAX->get_available_fields_html();
		remove_action( 'gravityview_render_available_fields', array( $this, 'get_available_fields_html_EDIT_CONTEXT' ), 10 );

		$this->assertTrue( true, 'This test is not actually risky; it is powered by filters. Prevent a risky warning.' );
	}



	/**
	 * @param array $form
	 * @param string $context
	 */
	function get_available_fields_html_DEFAULT( $form, $context ) {

		$this->assertEquals( GravityView_Ajax::pre_get_form_fields( $this->GravityView_Preset_Business_Data->template_id ), $form );

		// When not defined, default to directory
		$this->assertEquals( 'directory', $context );
	}

	/**
	 * @param array $form
	 * @param string $context
	 */
	function get_available_fields_html_SINGLE_CONTEXT( $form, $context ) {

		// When not defined, default to directory
		$this->assertEquals( 'single', $context );
	}

	/**
	 * @param array $form
	 * @param string $context
	 */
	function get_available_fields_html_EDIT_CONTEXT( $form, $context ) {

		// When not defined, default to directory
		$this->assertEquals( 'edit', $context );
	}

	/**
	 * @covers GravityView_Ajax::pre_get_form_fields()
	 * @group gvajax
	 */
	function test_pre_get_form_fields() {

		$imported_form = $this->AJAX->import_form( $this->GravityView_Preset_Business_Data->settings['preset_form'] );

		$not_imported_form = GravityView_Ajax::pre_get_form_fields( $this->GravityView_Preset_Business_Data->template_id );

		// We don't test exact equality, since the import_form will return GF_Field objects for field items, and other
		// differences. We just want to make sure most stuff matches close enough to suggest it's working!
		$this->assertEquals( count( $imported_form['fields'] ), count( $not_imported_form['fields'] ) );
		$this->assertEquals( $imported_form['title'], $not_imported_form['title'] );
	}

	/**
	 * @covers GravityView_Ajax::import_form()
	 * @group gvajax
	 */
	function test_import_form() {
		/** @define "GRAVITYVIEW_DIR" "../../" */

		$forms = $this->AJAX->import_form( $this->GravityView_Preset_Business_Data->settings['preset_form'] );

		$this->assertNotEmpty( $forms );
		$this->assertEquals( 'GravityView - Business Data', $forms['title'] );
		$this->assertEquals( 14, sizeof( $forms['fields'] ) );

		$GravityView_Preset_Business_Listings = new GravityView_Preset_Business_Listings;

		$forms = $this->AJAX->import_form( $GravityView_Preset_Business_Listings->settings['preset_form'] );

		$this->assertNotEmpty( $forms );
		$this->assertEquals( 'GravityView - Business Listing', $forms['title'] );
		$this->assertEquals( 13, sizeof( $forms['fields'] ) );
	}

	/**
	 * @covers GravityView_Ajax::save_view()
	 * @group gvajax
	 */
	function test_save_view_no_post_id() {
		// Set up AJAX context
		add_filter( 'wp_doing_ajax', '__return_true' );

		// No post_id provided
		unset( $_POST['post_id'] );

		// Expect wp_send_json_error to be called
		$this->expectException( 'WPDieException' );

		$this->AJAX->save_view();

		remove_filter( 'wp_doing_ajax', '__return_true' );
	}

	/**
	 * @covers GravityView_Ajax::save_view()
	 * @group gvajax
	 */
	function test_save_view_invalid_post_type() {
		// Set up AJAX context
		add_filter( 'wp_doing_ajax', '__return_true' );

		// Create a regular post instead of gravityview
		$post_id = $this->factory->post->create();
		$_POST['post_id'] = $post_id;

		// Expect wp_send_json_error to be called
		$this->expectException( 'WPDieException' );

		$this->AJAX->save_view();

		remove_filter( 'wp_doing_ajax', '__return_true' );
	}

	/**
	 * @covers GravityView_Ajax::save_view()
	 * @group gvajax
	 */
	function test_save_view_no_capability() {
		// Set up AJAX context
		add_filter( 'wp_doing_ajax', '__return_true' );

		// Create a GravityView post
		$post_id = $this->factory->post->create( array(
			'post_type' => 'gravityview'
		) );
		$_POST['post_id'] = $post_id;

		// Set user to subscriber (no edit capability)
		$user = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user );

		// Recreate nonce with new user
		$this->create_test_nonce();

		// Expect wp_send_json_error to be called
		$this->expectException( 'WPDieException' );

		$this->AJAX->save_view();

		remove_filter( 'wp_doing_ajax', '__return_true' );
	}

	/**
	 * @covers GravityView_Ajax::save_view()
	 * @group gvajax
	 */
	function test_save_view_success() {
		// Set up AJAX context
		add_filter( 'wp_doing_ajax', '__return_true' );

		// Create admin user with proper capabilities
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		// Recreate nonce with admin user
		$this->create_test_nonce();

		// Create a GravityView post
		$post_id = $this->factory->post->create( array(
			'post_type'   => 'gravityview',
			'post_title'  => 'Test View',
			'post_status' => 'publish'
		) );
		$_POST['post_id'] = $post_id;

		// Mock the admin views instance and its methods
		$mock_admin_views = $this->getMockBuilder( 'GravityView_Admin_Views' )
			->disableOriginalConstructor()
			->getMock();

		$mock_admin_views->method( 'prepare_view_data_from_request' )
			->willReturn( array(
				'form_id' => 1,
				'template_id' => 'default_table'
			) );

		$mock_admin_views->method( 'process_view_save_data' )
			->willReturn( array(
				'updated' => true
			) );

		// Replace the real instance with our mock
		$reflection = new ReflectionClass( 'GravityView_Admin_Views' );
		$instance_property = $reflection->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, $mock_admin_views );

		// Capture the JSON output
		ob_start();

		try {
			$this->AJAX->save_view();
		} catch ( WPDieException $e ) {
			$output = ob_get_clean();
			$response = json_decode( $output, true );

			// Verify success response
			$this->assertTrue( $response['success'] );
			$this->assertEquals( $post_id, $response['data']['post_id'] );
			$this->assertArrayHasKey( 'message', $response['data'] );
			$this->assertArrayHasKey( 'post_modified', $response['data'] );
			$this->assertArrayHasKey( 'post_modified_gmt', $response['data'] );
		}

		ob_end_clean();
		remove_filter( 'wp_doing_ajax', '__return_true' );
	}

	/**
	 * @covers GravityView_Ajax::save_view()
	 * @group gvajax
	 */
	function test_save_view_post_locked() {
		// Set up AJAX context
		add_filter( 'wp_doing_ajax', '__return_true' );

		// Create admin user
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		// Recreate nonce with admin user
		$this->create_test_nonce();

		// Create another user who has the post locked
		$other_user = $this->factory->user->create( array( 'role' => 'administrator' ) );

		// Create a GravityView post
		$post_id = $this->factory->post->create( array(
			'post_type'   => 'gravityview',
			'post_title'  => 'Test View',
			'post_status' => 'publish'
		) );
		$_POST['post_id'] = $post_id;

		// Set post lock for other user
		update_post_meta( $post_id, '_edit_lock', sprintf( '%s:%s', time(), $other_user ) );

		// Expect wp_send_json_error to be called with 409 status
		$this->expectException( 'WPDieException' );

		ob_start();
		try {
			$this->AJAX->save_view();
		} catch ( WPDieException $e ) {
			$output = ob_get_clean();
			$response = json_decode( $output, true );

			// Verify error response for locked post
			$this->assertFalse( $response['success'] );
			$this->assertStringContainsString( 'currently being edited', $response['data']['message'] );
		}

		ob_end_clean();
		remove_filter( 'wp_doing_ajax', '__return_true' );
	}
}
