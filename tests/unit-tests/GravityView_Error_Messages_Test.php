<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * Test the Error_Messages class for capability-based error messaging
 *
 * @group gvfuture
 * @group error-messages
 *
 * @covers \GravityView_Error_Messages
 */
class GravityView_Error_Messages_Test extends GV_UnitTestCase {

	/**
	 * @var int Admin user ID
	 */
	private $admin_user_id;

	/**
	 * @var int Regular user ID
	 */
	private $user_id;

	/**
	 * @var \GV\View Test view
	 */
	private $view;

	/**
	 * @var int Form ID
	 */
	private $form_id;

	/**
	 * Set up test environment
	 */
	function setUp() : void {
		parent::setUp();

		$this->form_id = $this->factory->form->create();

		// Create admin user with edit_gravityview capability
		$this->admin_user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );

		// Create regular logged-in user
		$this->user_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );

		// Create a test view
		$view_id = $this->factory->view->create( [
			'form_id'       => $this->form_id,
			'post_status'   => 'publish',
			'post_title'    => 'Test View',
		] );

		$this->view = \GV\View::by_id( $view_id );
	}



	/**
	 * Test differentiated error message for admin - embed_only
	 *
	 * @covers \GV\Error_Messages::get()
	 * @covers \GV\Error_Messages::get_message_for_level()
	 */
	public function test_embed_only_error_admin() {
		wp_set_current_user( $this->admin_user_id );

		$message = \GravityView_Error_Messages::get( 'embed_only', $this->view, 'shortcode' );

		$this->assertStringContainsString( 'Embed Only', $message );
		$this->assertStringContainsString( '<a href=', $message, 'Admin message should contain action links' );
		$this->assertStringContainsString( 'Change this setting', $message );
		$this->assertStringContainsString( 'learn more', $message );
	}

	/**
	 * Test differentiated error message for user - embed_only
	 *
	 * @covers \GV\Error_Messages::get()
	 * @covers \GV\Error_Messages::get_message_for_level()
	 */
	public function test_embed_only_error_user() {
		wp_set_current_user( $this->user_id );

		$message = \GravityView_Error_Messages::get( 'embed_only', $this->view, 'shortcode' );

		$this->assertEquals( 'You are not allowed to view this content.', $message );
	}

	/**
	 * Test that the `get()` method accepts a `WP_Error` object as the first parameter.
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_get_accepts_wp_error_object() {
		$error = new \WP_Error( 'gravityview/entry_not_approved' );
		$message = \GravityView_Error_Messages::get( $error );
		$this->assertEquals( 'You are not allowed to view this content.', $message );
	}

	/**
	 * Test generic error message for public - embed_only
	 *
	 * @covers \GV\Error_Messages::get()
	 * @covers \GV\Error_Messages::get_message_for_level()
	 */
	public function test_embed_only_error_public() {
		wp_set_current_user( 0 );

		$message = \GravityView_Error_Messages::get( 'embed_only', $this->view, 'shortcode' );

		$this->assertEquals( 'You are not allowed to view this content.', $message );
	}

	/**
	 * Test that non-admin users get a generic message for entry moderation errors.
	 *
	 * @covers \GV\Error_Messages::get()
	 * @covers \GV\Error_Messages::get_message_for_level()
	 */
	public function test_get_generic_message_for_entry_moderation_error_non_admin() {
		wp_set_current_user( $this->user_id );
		$message = \GravityView_Error_Messages::get( 'entry_not_approved' );
		$this->assertEquals( 'You are not allowed to view this content.', $message );
	}

	/**
	 * Test differentiated error message for admin - no_direct_access
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_no_direct_access_error_admin() {
		wp_set_current_user( $this->admin_user_id );

		$message = \GravityView_Error_Messages::get( 'no_direct_access', $this->view, 'shortcode' );

		$this->assertStringContainsString( 'Direct access', $message );
		$this->assertStringContainsString( 'gravityview_direct_access filter', $message );
		$this->assertStringContainsString( '<a href=', $message );
	}

	/**
	 * Test differentiated error message for admin - not_public
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_not_public_error_admin() {
		wp_set_current_user( $this->admin_user_id );

		$message = \GravityView_Error_Messages::get( 'not_public', $this->view, 'shortcode' );

		$this->assertStringContainsString( 'not publicly visible', $message );
		$this->assertStringContainsString( '<a href=', $message );
	}

	/**
	 * Test differentiated error message for admin - rest_disabled
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_rest_disabled_error_admin() {
		wp_set_current_user( $this->admin_user_id );

		$message = \GravityView_Error_Messages::get( 'rest_disabled', $this->view, 'shortcode' );

		$this->assertStringContainsString( 'REST API', $message );
		$this->assertStringContainsString( 'disabled', $message );
		$this->assertStringContainsString( '<a href=', $message );
	}

	/**
	 * Test differentiated error message for admin - csv_disabled
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_csv_disabled_error_admin() {
		wp_set_current_user( $this->admin_user_id );

		$message = \GravityView_Error_Messages::get( 'csv_disabled', $this->view, 'shortcode' );

		$this->assertStringContainsString( 'CSV export', $message );
		$this->assertStringContainsString( 'disabled', $message );
		$this->assertStringContainsString( '<a href=', $message );
	}

	/**
	 * Test differentiated error message for admin - no_form_attached
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_no_form_attached_error_admin() {
		wp_set_current_user( $this->admin_user_id );

		$message = \GravityView_Error_Messages::get( 'no_form_attached', $this->view, 'shortcode' );

		$this->assertStringContainsString( 'This View is not configured properly', $message );
		$this->assertStringContainsString( '<a href=', $message );
	}

	/**
	 * Test generic error for entry_not_found (enumeration prevention)
	 *
	 * @covers \GV\Error_Messages::get()
	 * @covers \GV\Error_Messages::get_generic_message()
	 */
	public function test_entry_not_found_stays_generic() {
		// Test all capability levels return generic message
		$generic_message = 'You are not allowed to view this content.';

		wp_set_current_user( $this->admin_user_id );
		$this->assertEquals( $generic_message, \GravityView_Error_Messages::get( 'entry_not_found', $this->view ) );

		wp_set_current_user( $this->user_id );
		$this->assertEquals( $generic_message, \GravityView_Error_Messages::get( 'entry_not_found', $this->view ) );

		wp_set_current_user( 0 );
		$this->assertEquals( $generic_message, \GravityView_Error_Messages::get( 'entry_not_found', $this->view ) );
	}

	/**
	 * Test generic error for entry_wrong_form (enumeration prevention)
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_entry_wrong_form_stays_generic() {
		$generic_message = 'You are not allowed to view this content.';

		wp_set_current_user( $this->admin_user_id );
		$this->assertEquals( $generic_message, \GravityView_Error_Messages::get( 'entry_form_mismatch', $this->view ) );

		wp_set_current_user( $this->user_id );
		$this->assertEquals( $generic_message, \GravityView_Error_Messages::get( 'entry_form_mismatch', $this->view ) );

		wp_set_current_user( 0 );
		$this->assertEquals( $generic_message, \GravityView_Error_Messages::get( 'entry_form_mismatch', $this->view ) );
	}

	/**
	 * Test generic error for entry_slug_mismatch (enumeration prevention)
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_entry_slug_mismatch_stays_generic() {
		$generic_message = 'You are not allowed to view this content.';

		wp_set_current_user( $this->admin_user_id );
		$this->assertEquals( $generic_message, \GravityView_Error_Messages::get( 'entry_slug_mismatch', $this->view ) );
	}

	/**
	 * Test generic error for entry_not_active (enumeration prevention)
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_entry_not_active_stays_generic() {
		$generic_message = 'You are not allowed to view this content.';

		wp_set_current_user( $this->admin_user_id );
		$this->assertEquals( $generic_message, \GravityView_Error_Messages::get( 'entry_not_active', $this->view ) );
	}

	/**
	 * Test generic error for entry_not_approved (enumeration prevention)
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_entry_not_approved_stays_generic() {
		$generic_message = 'You are not allowed to view this content.';

		wp_set_current_user( $this->admin_user_id );
		$this->assertEquals( $generic_message, \GravityView_Error_Messages::get( 'entry_not_approved', $this->view ) );
	}

	/**
	 * Test unknown error code returns generic message
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_unknown_error_code_returns_generic() {
		$generic_message = 'You are not allowed to view this content.';

		wp_set_current_user( $this->admin_user_id );
		$this->assertEquals( $generic_message, \GravityView_Error_Messages::get( 'unknown_error_code', $this->view ) );
	}

	/**
	 * Test error code normalization (removes gravityview/ prefix)
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_error_code_normalization() {
		wp_set_current_user( $this->admin_user_id );

		$message1 = \GravityView_Error_Messages::get( 'embed_only', $this->view );
		$message2 = \GravityView_Error_Messages::get( 'gravityview/embed_only', $this->view );

		$this->assertEquals( $message1, $message2, 'Error codes with and without prefix should return same message' );
	}

	/**
	 * Test invalid error code returns generic message (input validation)
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_invalid_error_code_returns_generic() {
		wp_set_current_user( $this->admin_user_id );

		// Test with completely invalid error code
		$message = \GravityView_Error_Messages::get( 'totally_invalid_error_code', $this->view );
		$this->assertEquals( 'You are not allowed to view this content.', $message );
	}

	/**
	 * Test that an admin without specific GravityView capabilities still gets a generic message for moderation errors.
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_get_generic_message_for_entry_moderation_error_admin_no_cap() {
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		// Admin without specific GV caps should still get generic message for moderation errors
		$message = \GravityView_Error_Messages::get( 'entry_not_approved' );
		$this->assertEquals( 'You are not allowed to view this content.', $message );

		// Test with potential filter hook injection attempt
		$message = \GravityView_Error_Messages::get( '../../../malicious', $this->view );
		$this->assertEquals( 'You are not allowed to view this content.', $message );

		// Test with special characters
		$message = \GravityView_Error_Messages::get( 'error<script>alert(1)</script>', $this->view );
		$this->assertEquals( 'You are not allowed to view this content.', $message );
	}

	/**
	 * Test documentation links are correctly formatted
	 *
	 * @covers \GV\Error_Messages::get_docs_link()
	 */
	public function test_documentation_links() {
		wp_set_current_user( $this->admin_user_id );

		$message = \GravityView_Error_Messages::get( 'embed_only', $this->view );

		$this->assertStringContainsString( 'docs.gravitykit.com', $message );
		// $this->assertStringContainsString( '#embed-only', $message ); // Anchor not currently used
	}

	/**
	 * Test null view parameter handling
	 *
	 * @covers \GV\Error_Messages::get()
	 */
	public function test_null_view_parameter() {
		wp_set_current_user( 0 ); // Public user

		$message = \GravityView_Error_Messages::get( 'embed_only', null, 'shortcode' );

		$this->assertEquals( 'You are not allowed to view this content.', $message );
	}


	/**
	 * Test that user upgrading capability sees upgraded message
	 *
	 * @covers \GV\Error_Messages::get()
	 * @covers \GV\Error_Messages::get_capability_level()
	 */
	public function test_capability_upgrade() {
		// Start as public user
		wp_set_current_user( 0 );
		$message_public = \GravityView_Error_Messages::get( 'embed_only', $this->view );
		$this->assertEquals( 'You are not allowed to view this content.', $message_public );

		// Upgrade to logged-in user
		wp_set_current_user( $this->user_id );
		$message_user = \GravityView_Error_Messages::get( 'embed_only', $this->view );
		// Regular users get generic message
		$this->assertEquals( 'You are not allowed to view this content.', $message_user );
		$this->assertEquals( $message_public, $message_user );

		// Upgrade to admin
		wp_set_current_user( $this->admin_user_id );
		$message_admin = \GravityView_Error_Messages::get( 'embed_only', $this->view );
		$this->assertStringContainsString( 'Embed Only', $message_admin );
		$this->assertStringContainsString( '<a href=', $message_admin );
		$this->assertNotEquals( $message_user, $message_admin );
	}

	/**
	 * Clean up after tests
	 */
	function tearDown() : void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}
}
