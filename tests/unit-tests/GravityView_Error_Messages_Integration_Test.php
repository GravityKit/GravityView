<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * Integration tests for Error_Messages across different contexts
 *
 * Tests the error message system integration with:
 * - Shortcode handler
 * - oEmbed handler
 * - REST API handler
 *
 * @group gvfuture
 * @group error-messages
 * @group integration
 *
 * @covers \GravityView_Error_Messages
 * @covers \GV\Shortcode_gravityview
 * @covers \GV\oEmbed_View
 */
class GravityView_Error_Messages_Integration_Test extends GV_UnitTestCase {

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
	 * @var int Entry ID
	 */
	private $entry_id;

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
			'post_title'    => 'Integration Test View',
			'settings'      => [
				'show_only_approved' => '0',
			],
		] );

		$this->view = \GV\View::by_id( $view_id );

		// Create a test entry
		$this->entry_id = $this->factory->entry->create( [
			'form_id' => $this->form_id,
			'status'  => 'active',
		] );
	}

	/**
	 * Test shortcode context with differentiated error (embed_only)
	 *
	 * @covers \GV\Shortcode_gravityview::callback()
	 */
	public function test_shortcode_embed_only_error_admin() {
		wp_set_current_user( $this->admin_user_id );

		// Simulate embed_only View setting
		$this->view->settings->update( [ 'embed_only' => '1' ] );

		// The shortcode should use Error_Messages::get() which returns capability-aware message
		$message = \GravityView_Error_Messages::get( 'embed_only', $this->view, 'shortcode' );

		$this->assertStringContainsString( 'Embed Only', $message );
		$this->assertStringContainsString( '<a href=', $message );
	}

	/**
	 * Test shortcode context with generic error (entry_not_active)
	 *
	 * @covers \GV\Shortcode_gravityview::callback()
	 */
	public function test_shortcode_entry_not_active_stays_generic() {
		wp_set_current_user( $this->admin_user_id );

		// Entry-level errors should always return generic message
		$message = \GravityView_Error_Messages::get( 'entry-not-active', $this->view, 'shortcode' );

		$this->assertEquals( 'You are not allowed to view this content.', $message );
	}

	/**
	 * Test REST API context with generic error (entry_not_active)
	 */
	public function test_rest_api_entry_not_active_error() {
		$entry = $this->factory->entry->create_and_get( [ 'form_id' => $this->form_id, 'status' => 'trash' ] );

		// Mock the global request
		$request = new \GV\Mock_Request();
		$request->returns['is_view'] = true;
		$request->returns['is_entry'] = $entry;

		$view = \GV\View::by_id( $this->view->ID );
		$entry = \GV\GF_Entry::from_entry( $entry );

		// Check access.
		$error = $entry->check_access( $view, $request );

		$this->assertTrue( is_wp_error( $error ) );
		$this->assertEquals( 'gravityview/entry_not_active', $error->get_error_code() );

		$message = \GravityView_Error_Messages::get( $error );
		$this->assertEquals( 'You are not allowed to view this content.', $message );
	}

	/**
	 * Test oEmbed context with differentiated error (not_public)
	 */
	public function test_oembed_not_public_error_admin() {
		wp_set_current_user( $this->admin_user_id );

		// Change view to draft status
		wp_update_post( [
			'ID'          => $this->view->ID,
			'post_status' => 'draft',
		] );

		$this->view = \GV\View::by_id( $this->view->ID );

		$message = \GravityView_Error_Messages::get( 'not_public', $this->view, 'oembed' );

		$this->assertStringContainsString( 'not publicly visible', $message );
		$this->assertStringContainsString( '<a href=', $message );
	}

	/**
	 * Test oEmbed context with generic error (entry_not_found)
	 */
	public function test_oembed_entry_not_found_stays_generic() {
		wp_set_current_user( $this->admin_user_id );

		$message = \GravityView_Error_Messages::get( 'entry-not-found', $this->view, 'oembed' );

		$this->assertEquals( 'You are not allowed to view this content.', $message );
	}

	/**
	 * Test REST API context with differentiated error (rest_disabled)
	 */
	public function test_rest_api_rest_disabled_error_admin() {
		wp_set_current_user( $this->admin_user_id );

		$message = \GravityView_Error_Messages::get( 'rest_disabled', $this->view, 'rest' );

		$this->assertStringContainsString( 'REST API', $message );
		$this->assertStringContainsString( 'disabled', $message );
		$this->assertStringContainsString( 'http', $message );
	}

	/**
	 * Test REST API context with generic error (entry_wrong_form)
	 */
	public function test_rest_api_entry_wrong_form_stays_generic() {
		wp_set_current_user( $this->admin_user_id );

		$message = \GravityView_Error_Messages::get( 'entry-form-mismatch', $this->view, 'rest' );

		$this->assertEquals( 'You are not allowed to view this content.', $message );
	}

	/**
	 * Test shortcode context with generic error (entry_not_approved)
	 */
	public function test_shortcode_entry_not_approved_error() {
		$this->view->settings->set( 'show_only_approved', 1 );
		$entry = $this->factory->entry->create_and_get( [ 'form_id' => $this->form_id, 'status' => 'active' ] );
		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::DISAPPROVED );

		// Mock the global request
		$request = new \GV\Mock_Request();
		$request->returns['is_view'] = true;
		$request->returns['is_entry'] = $entry;

		$view = \GV\View::by_id( $this->view->ID );
		$entry = \GV\GF_Entry::from_entry( $entry );

		// Check access.
		$error = $entry->check_access( $view, $request );

		$this->assertTrue( is_wp_error( $error ) );
		$this->assertEquals( 'gravityview/entry_not_approved', $error->get_error_code() );

		$message = \GravityView_Error_Messages::get( $error );
		$this->assertEquals( 'You are not allowed to view this content.', $message );
	}

	/**
	 * Test multi-context consistency
	 */
	public function test_multi_context_consistency() {
		// Same error code should return same type of message across contexts
		wp_set_current_user( $this->admin_user_id );

		$shortcode_msg = \GravityView_Error_Messages::get( 'entry-not-found', $this->view, 'shortcode' );
		$oembed_msg = \GravityView_Error_Messages::get( 'entry-not-found', $this->view, 'oembed' );
		$rest_msg = \GravityView_Error_Messages::get( 'entry-not-found', $this->view, 'rest' );

		// All should be generic for enumeration prevention
		$this->assertEquals( $shortcode_msg, $oembed_msg );
		$this->assertEquals( $oembed_msg, $rest_msg );
		$this->assertEquals( 'You are not allowed to view this content.', $shortcode_msg );
	}

	/**
	 * Test all differentiated errors work across contexts
	 */
	public function test_all_differentiated_errors_across_contexts() {
		wp_set_current_user( $this->admin_user_id );

		$differentiated_errors = [
			'embed_only',
			'no_direct_access',
			'not_public',
			'rest_disabled',
			'csv_disabled',
			'no_form_attached',
		];

		$contexts = [ 'shortcode', 'oembed', 'rest' ];

		foreach ( $differentiated_errors as $error_code ) {
			foreach ( $contexts as $context ) {
				$message = \GravityView_Error_Messages::get( $error_code, $this->view, $context );

				// Admin messages should contain actionable links
				if ( 'rest' === $context ) {
					$this->assertStringContainsString( 'http', $message, "Error {$error_code} in {$context} context should have admin links" );
				} else {
					$this->assertStringContainsString( '<a href=', $message, "Error {$error_code} in {$context} context should have admin links" );
				}

				// Should not be generic message
				$this->assertNotEquals( 'You are not allowed to view this content.', $message, "Error {$error_code} in {$context} context should be differentiated for admin" );
			}
		}
	}

	/**
	 * Test all generic errors stay generic across contexts
	 */
	public function test_all_generic_errors_stay_generic_across_contexts() {
		wp_set_current_user( $this->admin_user_id );

		$generic_errors = [
			'entry-not-found',
			'entry-form-mismatch',
			'entry-slug-mismatch',
			'entry-not-active',
			'entry-not-approved',
		];

		$contexts = [ 'shortcode', 'oembed', 'rest' ];

		foreach ( $generic_errors as $error_code ) {
			foreach ( $contexts as $context ) {
				$message = \GravityView_Error_Messages::get( $error_code, $this->view, $context );

				// Should always be generic, even for admin
				$this->assertEquals( 'You are not allowed to view this content.', $message, "Error {$error_code} in {$context} context should stay generic" );
			}
		}
	}

	/**
	 * Test capability upgrade flow across contexts
	 */
	public function test_capability_upgrade_across_contexts() {
		$contexts = [ 'shortcode', 'oembed', 'rest' ];

		foreach ( $contexts as $context ) {
			// Public user
			wp_set_current_user( 0 );
			$public_msg = \GravityView_Error_Messages::get( 'embed_only', $this->view, $context );
			$this->assertEquals( 'You are not allowed to view this content.', $public_msg, "Public in {$context}" );

			// Regular user
			wp_set_current_user( $this->user_id );
			$user_msg = \GravityView_Error_Messages::get( 'embed_only', $this->view, $context );
			// Regular users get the generic message
			$this->assertEquals( 'You are not allowed to view this content.', $user_msg, "User in {$context}" );
			$this->assertEquals( $public_msg, $user_msg, "User message should match public in {$context}" );

			// Admin user
			wp_set_current_user( $this->admin_user_id );
			$admin_msg = \GravityView_Error_Messages::get( 'embed_only', $this->view, $context );
			$this->assertStringContainsString( 'Embed Only', $admin_msg, "Admin in {$context}" );
			if ( 'rest' === $context ) {
				$this->assertStringContainsString( 'http', $admin_msg, "Admin links in {$context}" );
			} else {
				$this->assertStringContainsString( '<a href=', $admin_msg, "Admin links in {$context}" );
			}
			$this->assertNotEquals( $user_msg, $admin_msg, "Admin message should differ from user in {$context}" );
		}
	}

	/**
	 * Clean up after tests
	 */
	function tearDown() : void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}
}
