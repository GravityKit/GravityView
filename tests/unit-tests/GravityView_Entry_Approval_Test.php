<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @since 1.18
 * @group approval
 */
class GravityView_Entry_Approval_Test extends GV_UnitTestCase {

	private $entries = array();

	private $form = array();

	private $form_id = 0;

	public function setUp() : void {
		parent::setUp();

		$this->form = $this->factory->form->create_and_get();

		$this->form_id = $this->form['id'];

		$this->entries = $this->factory->entry->create_many( 10, array( 'form_id' => $this->form_id, 'date_created' => '2013-11-28 11:00', '1' => 'Second Choice', '2.2' => 'Second Choice', '8' => '1', '13.6' => 'Spain' ) );
	}

	/**
	 * @covers GravityView_Entry_Approval::add_approval_notification_events
	 */
	public function test_add_approval_notification_events() {

		$events = apply_filters( 'gform_notification_events', array() );

		$this->assertArrayHasKey( 'gravityview/approve_entries/approved', $events );
		$this->assertArrayHasKey( 'gravityview/approve_entries/disapproved', $events );
		$this->assertArrayHasKey( 'gravityview/approve_entries/unapproved', $events );
		$this->assertArrayHasKey( 'gravityview/approve_entries/updated', $events );
	}

	/**
	 * @covers GravityView_Notifications::send_notifications()
	 * @covers GravityView_Entry_Approval::_trigger_notifications()
	 */
	public function test_send_notifications() {


		$this->assertTrue( ! did_action( 'gform_notification' ) );

		do_action( 'gravityview/approve_entries/disapproved', 0 );

		$this->assertTrue( ! did_action( 'gform_notification' ), 'Filter should not have run because entry ID was invalid.' );

		$notifications = array(
			array(
				'name'  => 'Approved',
				'id'    => 1,
				'event' => 'gravityview/approve_entries/approved',
			),
			array(
				'name'  => 'Disapproved',
				'id'    => 2,
				'event' => 'gravityview/approve_entries/disapproved',
			),
			array(
				'name'  => 'Unapproved',
				'id'    => 3,
				'event' => 'gravityview/approve_entries/unapproved',
			),
			array(
				'name'  => 'Updated',
				'id'    => 4,
				'event' => 'gravityview/approve_entries/updated',
			),
		);

		$test_form  = $this->factory->form->create_and_get( array( 'notifications' => $notifications ) );
		$test_entry = $this->factory->entry->create_and_get( array( 'form_id' => $test_form['id'] ) );

		$this->assertTrue( is_array( $test_entry ), 'Entry was not created properly' );

		$triggered_notifications = array();

		$test_object = & $this;

		foreach( $notifications as $test_notification ) {

			$filter_notification = function( $notification, $form, $lead ) use ( $test_notification, $test_form, $test_entry, & $triggered_notifications, $test_object ) {
				$test_object->assertSame( $notification, $test_notification );
				$test_object->assertSame( $lead, $test_entry );
				$test_object->assertSame( $form, $test_form );
				$triggered_notifications[] = $test_notification;
				return $notification;
			};

			add_filter( 'gform_notification', $filter_notification, 10, 3 );

			do_action( $test_notification['event'], $test_entry['id'] );

			remove_filter( 'gform_notification', $filter_notification );
		}

		unset( $test_object );

		$this->assertSame( $notifications, $triggered_notifications );
	}

	/**
	 * @covers GravityView_Entry_Approval::after_submission
	 */
	public function test_after_submission() {

		$args = array( 'form_id' => $this->form_id, 'date_created' => '2013-11-28 11:00', '1' => 'Second Choice', '2.2' => 'Second Choice', '8' => '1', '13.6' => 'Spain' );

		$entry = $this->factory->entry->create_and_get( $args );

		$GravityView_Entry_Approval = new GravityView_Entry_Approval;

		$this->assertEquals( '', gform_get_meta( $entry['id'], GravityView_Entry_Approval::meta_key ), 'entry status should not be set, entry created via API' );

		$GravityView_Entry_Approval->after_submission( $entry, $this->form );

		$this->assertEquals( GravityView_Entry_Approval_Status::UNAPPROVED, (int) gform_get_meta( $entry['id'], GravityView_Entry_Approval::meta_key ), 'entry status should be set to unapproved' );

		gform_delete_meta( $entry['id'], GravityView_Entry_Approval::meta_key ); // Reset

		add_filter( 'gravityview/approve_entries/after_submission/default_status', function() {
			return 'NOT A VALID APPROVAL STATUS, MATE';
		});

		$GravityView_Entry_Approval->after_submission( $entry, $this->form );

		$this->assertEquals( GravityView_Entry_Approval_Status::UNAPPROVED, (int) gform_get_meta( $entry['id'], GravityView_Entry_Approval::meta_key ), 'entry status should be set to default (unapproved) since invalid filter value' );

		remove_all_filters( 'gravityview/approve_entries/after_submission/default_status' );


		gform_delete_meta( $entry['id'], GravityView_Entry_Approval::meta_key ); // Reset

		add_filter( 'gravityview/approve_entries/after_submission/default_status', function() {
			return GravityView_Entry_Approval_Status::APPROVED;
		});

		$GravityView_Entry_Approval->after_submission( $entry, $this->form );

		$this->assertEquals( GravityView_Entry_Approval_Status::APPROVED, (int) gform_get_meta( $entry['id'], GravityView_Entry_Approval::meta_key ), 'entry status should be set to approved because filter' );

		remove_all_filters( 'gravityview/approve_entries/after_submission/default_status' );
	}

	/**
	 * @since 1.18
	 * @covers GravityView_Entry_Approval::update_approved
	 * @covers GravityView_Cache::in_blocklist()
	 */
	public function test_update_approved() {

		$GVCache = new GravityView_Cache();

		// Remove the form from the blocklist
		$GVCache->blocklist_remove( $this->form_id );

		// Make sure form isn't in cache blocklist
		$this->assertFalse( $GVCache->in_blocklist( $this->form_id ) );

		$statuses = GravityView_Entry_Approval_Status::get_all();

		foreach ( $this->entries as $entry_id ) {

			// Default: Unapproved
			$this->assertEquals( GravityView_Entry_Approval_Status::UNAPPROVED, GravityView_Entry_Approval::get_entry_status( $entry_id, 'value' ) );

			// Set, then check, the status
			foreach ( $statuses as $status ) {
				GravityView_Entry_Approval::update_approved( $entry_id, $status['value'], $this->form_id );
				$this->assertEquals( $status['value'], GravityView_Entry_Approval::get_entry_status( $entry_id, 'value' ) );
			}
		}

		// Now that the entry has been updated, the form should be in the blocklist
		$this->assertTrue( $GVCache->in_blocklist( $this->form_id ) );

		// Invalid Entry ID
		$this->assertFalse( GravityView_Entry_Approval::update_approved( rand( 100000, 1000000000 ), GravityView_Entry_Approval_Status::APPROVED, $this->form_id ), 'Should have returned false; Invalid entry ID' );
	}

	/**
	 * Ensures no approval actions/notifications fire when approval status doesn't change
	 * even if the Approve Entries field is present on Edit Entry.
	 *
	 * @covers GravityView_Entry_Approval::after_update_entry_update_approved_meta
	 */
	public function test_no_notifications_when_status_unchanged_on_edit() {
		// Import a form that includes the Approve Entries checkbox field.
		$form = $this->factory->form->import_and_get( 'approval.json', 0 );

		// Create an entry with the approval checkbox checked and meta set to APPROVED.
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'foo',
			'2' => 'bar',
			'3.1' => 'Approved',
		) );

		// Set initial approval meta to APPROVED to match the checkbox value.
		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		$entry = GFAPI::get_entry( $entry['id'] );

		$this->assertSame( \GravityView_Entry_Approval_Status::APPROVED, GravityView_Entry_Approval::get_entry_status( $entry, 'value' ) );

		// Avoid registering hooks in constructor; we only need the method.
		$gv_approval = new class extends GravityView_Entry_Approval { public function __construct() {} };

		$updated_count_before     = did_action( 'gravityview/approve_entries/updated' );
		$approved_count_before    = did_action( 'gravityview/approve_entries/approved' );
		$disapproved_count_before = did_action( 'gravityview/approve_entries/disapproved' );
		$unapproved_count_before  = did_action( 'gravityview/approve_entries/unapproved' );

		// Simulate Edit Entry saving without changing the approval field.
		$gv_approval->after_update_entry_update_approved_meta( $form, $entry['id'] );

		// Expect: no approval actions fired because status did not change.
		$this->assertSame( $updated_count_before, did_action( 'gravityview/approve_entries/updated' ) );
		$this->assertSame( $approved_count_before, did_action( 'gravityview/approve_entries/approved' ) );
		$this->assertSame( $disapproved_count_before, did_action( 'gravityview/approve_entries/disapproved' ) );
		$this->assertSame( $unapproved_count_before, did_action( 'gravityview/approve_entries/unapproved' ) );

		// Control: now change the checkbox to blank (disapproved) so a change occurs.
		$entry['3.1'] = '';
		GFAPI::update_entry( $entry );

		$gv_approval->after_update_entry_update_approved_meta( $form, $entry['id'] );

		// Expect: actions incremented for updated and disapproved.
		$this->assertSame( $updated_count_before + 1, did_action( 'gravityview/approve_entries/updated' ) );
		$this->assertSame( $disapproved_count_before + 1, did_action( 'gravityview/approve_entries/disapproved' ) );
	}

	/**
	 * Ensures no approval actions/notifications fire when status remains Disapproved
	 * and does fire when changing to Approved.
	 *
	 * @covers GravityView_Entry_Approval::after_update_entry_update_approved_meta
	 */
	public function test_no_notifications_when_status_unchanged_on_edit_disapproved() {
		$form = $this->factory->form->import_and_get( 'approval.json', 0 );

		// Create an entry with the approval checkbox UNCHECKED (disapproved)
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'alpha',
			'2' => 'beta',
			'3.1' => '',
		) );

		// Set initial approval meta to DISAPPROVED to match checkbox
		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::DISAPPROVED );

		$entry = GFAPI::get_entry( $entry['id'] );

		$this->assertSame( \GravityView_Entry_Approval_Status::DISAPPROVED, GravityView_Entry_Approval::get_entry_status( $entry, 'value' ) );

		$gv_approval = new class extends GravityView_Entry_Approval { public function __construct() {} };

		$updated_before     = did_action( 'gravityview/approve_entries/updated' );
		$unapproved_before  = did_action( 'gravityview/approve_entries/unapproved' );
		$disapproved_before = did_action( 'gravityview/approve_entries/disapproved' );
		$approved_before    = did_action( 'gravityview/approve_entries/approved' );

		// Save without changing checkbox; expect no actions
		$gv_approval->after_update_entry_update_approved_meta( $form, $entry['id'] );
		$this->assertSame( $updated_before, did_action( 'gravityview/approve_entries/updated' ), 'Approval did not change, so no updated action should have been triggered' );
		$this->assertSame( $unapproved_before, did_action( 'gravityview/approve_entries/unapproved' ), 'Approval did not change, so no unapproved action should have been triggered' );
		$this->assertSame( $disapproved_before, did_action( 'gravityview/approve_entries/disapproved' ), 'Approval did not change, so no disapproved action should have been triggered' );
		$this->assertSame( $approved_before, did_action( 'gravityview/approve_entries/approved' ), 'Approval did not change, so no approved action should have been triggered' );

		// Change checkbox to Approved
		$entry['3.1'] = 'Approved';
		GFAPI::update_entry( $entry );

		$gv_approval->after_update_entry_update_approved_meta( $form, $entry['id'] );

		// Expect updated + approved incremented
		$this->assertSame( $updated_before + 1, did_action( 'gravityview/approve_entries/updated' ), 'These should have been triggered' );
		$this->assertSame( $approved_before + 1, did_action( 'gravityview/approve_entries/approved' ), 'These should have been triggered' );
		$this->assertSame( $unapproved_before, did_action( 'gravityview/approve_entries/unapproved' ), 'These should not have been triggered' );
		$this->assertSame( $disapproved_before, did_action( 'gravityview/approve_entries/disapproved' ), 'These should not have been triggered' );
	}

	/**
	 * Verifies that notes ARE added when approval status changes via Edit Entry.
	 *
	 * @since 2.48.2
	 *
	 * @covers GravityView_Entry_Approval::after_update_entry_update_approved_meta
	 */
	public function test_note_added_when_status_changes_on_edit() {
		$form = $this->factory->form->import_and_get( 'approval.json', 0 );

		// Create an entry with approval checkbox checked (APPROVED).
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'test',
			'2' => 'data',
			'3.1' => 'Approved',
		) );

		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );
		$entry = GFAPI::get_entry( $entry['id'] );

		$gv_approval = new class extends GravityView_Entry_Approval { public function __construct() {} };

		// Get initial note count
		$notes_before = \GravityView_Entry_Notes::get_notes( $entry['id'] );
		$notes_count_before = is_array( $notes_before ) ? count( $notes_before ) : 0;

		// Change status from APPROVED to DISAPPROVED
		$entry['3.1'] = '';
		GFAPI::update_entry( $entry );

		$gv_approval->after_update_entry_update_approved_meta( $form, $entry['id'] );

		// Verify a note was added
		$notes_after = \GravityView_Entry_Notes::get_notes( $entry['id'] );
		$notes_count_after = is_array( $notes_after ) ? count( $notes_after ) : 0;

		$this->assertSame( $notes_count_before + 1, $notes_count_after, 'A note should have been added when approval status changed' );

		// Verify the note content mentions the status change
		if ( is_array( $notes_after ) && ! empty( $notes_after ) ) {
			$latest_note = end( $notes_after );
			$this->assertNotNull( $latest_note, 'Latest note should exist' );

			// Notes can be objects or arrays depending on GF version
			$note_value = is_object( $latest_note ) ? $latest_note->value : $latest_note['value'];
			$this->assertStringContainsString( 'disapproved', strtolower( $note_value ), 'Note should mention disapproved status' );
		}
	}

	/**
	 * Verifies that notes are NOT added when approval status remains unchanged via Edit Entry.
	 *
	 * @since 2.48.2
	 *
	 * @covers GravityView_Entry_Approval::after_update_entry_update_approved_meta
	 */
	public function test_note_not_added_when_status_unchanged_on_edit() {
		$form = $this->factory->form->import_and_get( 'approval.json', 0 );

		// Create an entry with approval checkbox checked (APPROVED).
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'test',
			'2' => 'data',
			'3.1' => 'Approved',
		) );

		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );
		$entry = GFAPI::get_entry( $entry['id'] );

		$gv_approval = new class extends GravityView_Entry_Approval { public function __construct() {} };

		// Get initial note count
		$notes_before = \GravityView_Entry_Notes::get_notes( $entry['id'] );
		$notes_count_before = is_array( $notes_before ) ? count( $notes_before ) : 0;

		// Save without changing the approval status
		$gv_approval->after_update_entry_update_approved_meta( $form, $entry['id'] );

		// Verify no note was added
		$notes_after = \GravityView_Entry_Notes::get_notes( $entry['id'] );
		$notes_count_after = is_array( $notes_after ) ? count( $notes_after ) : 0;

		$this->assertSame( $notes_count_before, $notes_count_after, 'No note should have been added when approval status did not change' );
	}

	/**
	 * Verifies that notes are added when entry is auto-unapproved after editing.
	 *
	 * @since 2.48.2
	 *
	 * @covers GravityView_Entry_Approval::autounapprove
	 */
	public function test_note_added_on_autounapprove() {
		$form = $this->factory->form->create_and_get();

		// Create an approved entry
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'test',
		) );

		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		// Create a View with auto-unapprove enabled
		$view_id = $this->factory->view->create( array(
			'form_id' => $form['id'],
			'settings' => array(
				'unapprove_edit' => '1',
			),
		) );

		$view = \GV\View::by_id( $view_id );

		// Get initial note count
		$notes_before = \GravityView_Entry_Notes::get_notes( $entry['id'] );
		$notes_count_before = is_array( $notes_before ) ? count( $notes_before ) : 0;

		// Mock the edit context
		$edit = $this->getMockBuilder( 'GravityView_Edit_Entry_Render' )
			->disableOriginalConstructor()
			->getMock();

		// Create GravityView_View_Data using from_post method
		$gv_data = \GravityView_View_Data::getInstance( $view_id );

		// Set current user as non-moderator to trigger auto-unapprove
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Trigger auto-unapprove
		GravityView_Entry_Approval::autounapprove( $form, $entry['id'], $edit, $gv_data );

		// Verify a note was added
		$notes_after = \GravityView_Entry_Notes::get_notes( $entry['id'] );
		$notes_count_after = is_array( $notes_after ) ? count( $notes_after ) : 0;

		$this->assertSame( $notes_count_before + 1, $notes_count_after, 'A note should have been added when entry was auto-unapproved' );

		// Verify the note content mentions auto-unapproval
		if ( is_array( $notes_after ) && ! empty( $notes_after ) ) {
			$latest_note = end( $notes_after );
			$this->assertNotNull( $latest_note, 'Latest note should exist' );

			// Notes can be objects or arrays depending on GF version
			$note_value = is_object( $latest_note ) ? $latest_note->value : $latest_note['value'];
			$this->assertStringContainsString( 'automatically unapproved', strtolower( $note_value ), 'Note should mention automatic unapproval' );
		}
	}

	/**
	 * Verifies that update_approved_meta() returns boolean values correctly.
	 *
	 * @since 2.48.2
	 *
	 * @covers GravityView_Entry_Approval::update_approved_meta
	 */
	public function test_update_approved_meta_returns_boolean() {
		$update_approved_meta = new ReflectionMethod( 'GravityView_Entry_Approval', 'update_approved_meta' );
		$update_approved_meta->setAccessible( true );

		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $this->form_id ) );

		// Test: Returns true when successfully updating meta
		$result = $update_approved_meta->invoke( null, $entry['id'], GravityView_Entry_Approval_Status::APPROVED, $this->form_id );
		$this->assertTrue( $result, 'Should return true when meta is successfully updated' );

		// Test: Returns false when status is unchanged
		$result = $update_approved_meta->invoke( null, $entry['id'], GravityView_Entry_Approval_Status::APPROVED, $this->form_id );
		$this->assertFalse( $result, 'Should return false when status is unchanged' );

		// Test: Returns false for invalid status
		$result = $update_approved_meta->invoke( null, $entry['id'], 'INVALID_STATUS', $this->form_id );
		$this->assertFalse( $result, 'Should return false for invalid status' );
	}

	/**
	 * @covers GravityView_Entry_Approval::add_approval_status_updated_note
	 */
	public function test_add_approval_status_updated_note() {

		$add_approval_status = new ReflectionMethod( 'GravityView_Entry_Approval', 'add_approval_status_updated_note' );

		// It was private; let's make it public
		$add_approval_status->setAccessible( true );

		$entry_id = array_pop( $this->entries );

		$entry_note_id = $add_approval_status->invoke( new GravityView_Entry_Approval, $entry_id, GravityView_Entry_Approval_Status::APPROVED );

		$this->assertTrue( is_int( $entry_note_id ), 'The entry ID was not an integer, which is should have been' );

		// Prevent note from being added
		add_filter( 'gravityview/approve_entries/add-note', '__return_false' );

		$entry_note_response = $add_approval_status->invoke( new GravityView_Entry_Approval, $entry_id, GravityView_Entry_Approval_Status::APPROVED );

		$this->assertFalse( $entry_note_response, 'the "gravityview/approve_entries/add-note" filter did not work to prevent the entry from being added' );

		remove_filter( 'gravityview/approve_entries/add-note', '__return_false' );
	}


	/**
	 * @covers GravityView_Entry_Approval::update_bulk
	 */
	public function test_update_bulk() {

		wp_set_current_user( 0 );

		// Logged-out user doesn't have caps
		$this->assertNull( GravityView_Entry_Approval::update_bulk( $this->entries, GravityView_Entry_Approval_Status::APPROVED, $this->form_id ), 'Should have returned NULL; there is no logged-in user, so they should have failed the has_cap() test' );

		// Invalid status
		$this->assertNull( GravityView_Entry_Approval::update_bulk( $this->entries, 'TOTALLY INVALID', $this->form_id ), 'Should have returned NULL; Invalid status' );

		// Logged-in admin
		$this->factory->user->create_and_set( array( 'role' => 'administrator' ) );

		// Now check bulk editing each status value
		$statuses = GravityView_Entry_Approval_Status::get_all();

		foreach ( $statuses as $status ) {

			$updated = GravityView_Entry_Approval::update_bulk( $this->entries, $status['value'], $this->form_id );

			$this->assertTrue( $updated, 'update_bulk returned false' );

			foreach ( $this->entries as $entry ) {
				$this->assertEquals( $status['value'], GravityView_Entry_Approval::get_entry_status( $entry, 'value' ) );
			}
		}

		// Invalid Entry IDs
		$this->assertFalse( GravityView_Entry_Approval::update_bulk( range( 20000, 20010 ), GravityView_Entry_Approval_Status::APPROVED, $this->form_id ), 'Should have returned false; Invalid entry IDs' );
	}

	public function test_entry_list_filter_links() {
		// Discussion: https://gravitykit.slack.com/archives/C727B06MB/p1736354374221989
		$this->markTestSkipped('Flaky test due to $wpdb sometimes returning empty results');

		$form = $this->factory->form->create_and_get();

		$entry = $this->factory->entry->create_and_get( [ 'form_id' => $form['id'] ] );

		$approve_entries = new class extends GravityView_Admin_ApproveEntries { };

		$filter_links = $approve_entries->filter_links_entry_list( [], $form );

		$this->assertEquals( 0, $filter_links[0]['count'] );
		$this->assertEquals( 0, $filter_links[1]['count'] );
		$this->assertEquals( 1, $filter_links[2]['count'] );

		GravityView_Entry_Approval::update_approved( $entry['id'], GravityView_Entry_Approval_Status::APPROVED, $form['id'] );

		$filter_links = $approve_entries->filter_links_entry_list( [], $form );

		$this->assertEquals( 1, $filter_links[0]['count'] );
		$this->assertEquals( 0, $filter_links[1]['count'] );
		$this->assertEquals( 0, $filter_links[2]['count'] );

		GravityView_Entry_Approval::update_approved( $entry['id'], GravityView_Entry_Approval_Status::DISAPPROVED, $form['id'] );

		$filter_links = $approve_entries->filter_links_entry_list( [], $form );

		$this->assertEquals( 0, $filter_links[0]['count'] );
		$this->assertEquals( 1, $filter_links[1]['count'] );
		$this->assertEquals( 0, $filter_links[2]['count'] );
	}
}
