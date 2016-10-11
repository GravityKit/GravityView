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
