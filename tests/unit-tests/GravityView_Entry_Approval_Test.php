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

	public function setUp() {
		parent::setUp();

		$this->form = $this->factory->form->create_and_get();

		$this->form_id = $this->form['id'];

		$this->entries = $this->factory->entry->create_many( 10, array( 'form_id' => $this->form_id, 'date_created' => '2013-11-28 11:00', '1' => 'Second Choice', '2.2' => 'Second Choice', '8' => '1', '13.6' => 'Spain' ) );
	}

	/**
	 * @since 1.18
	 * @covers GravityView_Entry_Approval::update_approved
	 * @covers GravityView_Cache::in_blacklist()
	 */
	public function test_update_approved() {

		$GVCache = new GravityView_Cache();

		// Form isn't in cache blacklist yet
		$this->assertFalse( $GVCache->in_blacklist( $this->form_id ) );

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

		$this->assertTrue( $GVCache->in_blacklist( $this->form_id ) );

		// Invalid Entry ID
		$this->assertFalse( GravityView_Entry_Approval::update_approved( rand( 100000, 1000000000 ), GravityView_Entry_Approval_Status::APPROVED, $this->form_id ), 'Should have returned false; Invalid entry ID' );
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

}
