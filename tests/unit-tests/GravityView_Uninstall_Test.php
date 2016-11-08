<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @since 1.15
 * @group uninstall
 * Class GravityView_Uninstall_Test
 */
class GravityView_Uninstall_Test extends GV_UnitTestCase {

	function setUp() {
		parent::setUp();

		require_once GV_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/class-gravityview-settings.php';
		require_once GV_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/class-gravityview-uninstall.php';
	}

	/**
	 * @since 1.15
	 * @covers GravityView_Uninstall::fire_everything()
	 * @covers GravityView_Settings::uninstall()
	 * @covers GravityView_Settings::uninstall_addon()
	 */
	function test_fire_everything() {

		$create_count = 10;

		$form = $this->factory->form->create_and_get();

		$all_forms = GFAPI::get_forms();

		$views = $this->factory->view->create_many( $create_count, array( 'form_id' => $form['id'] ) );

		$entry_ids = $this->factory->entry->create_many( $create_count, array( 'form_id' => $form['id'], 'date_created' => '2013-11-28 11:00', '1' => 'Second Choice', '2.2' => 'Second Choice', '8' => '1', '13.6' => 'Spain' ) );

		$connected = gravityview_get_connected_views( $form['id'] );

		$entry_count = GFAPI::count_entries( $form['id'] );

		// Make sure the objects were created and connected
		$this->assertEquals( $create_count, count( array_filter( $views ) ) );
		$this->assertEquals( $create_count, count( array_filter( $connected ) ) );
		$this->assertEquals( $create_count, count( array_filter( $entry_ids ) ) );

		$this->_set_up_expected_options();

		### DO NOT DELETE WHEN THE USER DOESN'T HAVE THE CAPABILITY

		$user = $this->factory->user->create_and_set(array(
			'user_login'  => 'administrator',
			'user_pass'   => 'administrator',
			'role'        => 'administrator',
		));

		$this->assertTrue( GVCommon::has_cap( 'gravityview_uninstall' ) );

		// Create the items
		$this->_set_up_gravityview_settings();
		$this->_set_up_notes( $entry_ids );
		$this->_set_up_entry_meta( $entry_ids, $form );

		// Trigger GF Addon uninstall, which also triggers uninstall() method
		GravityView_Settings::get_instance()->uninstall_addon();

		// No Forms should be deleted
		$this->assertEquals( $all_forms, GFAPI::get_forms() );

		$this->_check_posts();
		$this->_check_entries( $form, $entry_count );
		$this->_check_deleted_options();
		$this->_check_deleted_entry_notes( $entry_ids );
		$this->_check_deleted_entry_meta( $entry_ids );

	}

	/**
	 * Make sure that the GV approval entry meta has been deleted, but not other meta
	 * @since 1.15
	 * @param $entry_ids
	 */
	function _check_deleted_entry_meta( $entry_ids ) {

		$values = gform_get_meta_values_for_entries( $entry_ids, array( 'is_approved', 'do_not_delete' ) );

		foreach ( $values as $value ) {
			$this->assertFalse( $value->is_approved );
			$this->assertEquals( "DO NOT DELETE", $value->do_not_delete );
		}
	}

	/**
	 * @since 1.15
	 */
	function _check_posts() {
		// All the Views should be deleted
		$views = get_posts( array(
			'post_type' => 'gravityview',
		));
		$this->assertEquals( array(), $views );
	}

	/**
	 * No entries should be deleted
	 * @since 1.15
	 * @param $form
	 * @param $create_count
	 */
	function _check_entries( $form, $create_count ) {
		$entries = GFAPI::get_entries( $form['id'] );
		$this->assertEquals( sizeof( $entries ), $create_count );
	}

	/**
	 * There should only be ONE NOTE not deleted
	 * @since 1.15
	 * @param $entry_ids
	 */
	function _check_deleted_entry_notes( $entry_ids ) {

		foreach( $entry_ids as $entry_id ) {

			$notes = GravityView_Entry_Notes::get_notes( $entry_id );

			$this->assertEquals( sizeof( $notes ), 1 );
			$this->assertEquals( 'NOT DELETED', $notes[0]->value );
		}
	}

	/**
	 * Make sure the settings and transients have been deleted
	 * @since 1.15
	 */
	function _check_deleted_options( $should_be_empty = true ) {

		$options = array(
			'gravityformsaddon_gravityview_app_settings',
			'gravityformsaddon_gravityview_version',
			'gravityview_cache_blacklist',
		);

		foreach( $options as $option ) {
			if( $should_be_empty ) {
				$this->assertEmpty( get_option( $option ), $option . ' is not empty' );
			} else {
				$this->assertNotEmpty( get_option( $option ), $option  . ' is empty' );
			}
		}

		$transients = array(
			'gravityview_edd-activate_valid',
			'gravityview_edd-deactivate_valid',
			'gravityview_dismissed_notices',
		);

		foreach( $transients as $transient ) {
			if( $should_be_empty ) {
				$this->assertEmpty( get_transient( $transient ), $transient . ' is not empty' );
			} else {
				$this->assertNotEmpty( get_transient( $transient ), $transient . ' is empty' );
			}
		}

		if( $should_be_empty ) {
			$this->assertEmpty( get_site_transient( 'gravityview_related_plugins' ), 'gravityview_related_plugins is not empty' );
		} else {
			$this->assertNotEmpty( get_site_transient( 'gravityview_related_plugins' ), 'gravityview_related_plugins is empty' );
		}
	}

	/**
	 * @since 1.15
	 * @param $entry_ids
	 * @param $form
	 */
	function _set_up_entry_meta( $entry_ids, $form ) {

		GravityView_Entry_Approval::update_bulk( $entry_ids, GravityView_Entry_Approval_Status::APPROVED, $form['id'] );

		foreach( $entry_ids as $entry_id ) {
			gform_add_meta( $entry_id, 'do_not_delete', 'DO NOT DELETE' );
		}
	}

	/**
	 * @since 1.15
	 * @param $entry_ids
	 */
	function _set_up_notes( $entry_ids ) {

		$disapproved = __('Disapproved the Entry for GravityView', 'gravityview');
		$approved = __('Approved the Entry for GravityView', 'gravityview');

		foreach( $entry_ids as $entry_id ) {

			$added_notes = 0;

			// Deleted because it's "gravityview" note type
			GravityView_Entry_Notes::add_note( $entry_id, -1, ( new WP_UnitTest_Generator_Sequence( 'To be deleted %s' ) )->get_template_string(), 'NOTE!', 'gravityview' ); // TO BE DELETED
			$added_notes++;

			// Deleted because it's the same value as $approved
			GravityView_Entry_Notes::add_note( $entry_id, -1, ( new WP_UnitTest_Generator_Sequence( 'To be deleted %s' ) )->get_template_string(), $approved, 'note' );
			$added_notes++;

			// Deleted because it's the same value as $disapproved
			GravityView_Entry_Notes::add_note( $entry_id, -1, ( new WP_UnitTest_Generator_Sequence( 'To be deleted %s' ) )->get_template_string(), $disapproved, 'note' );
			$added_notes++;

			// NOT DELETED
			GravityView_Entry_Notes::add_note( $entry_id, -1, ( new WP_UnitTest_Generator_Sequence( 'NOT DELETED %s' ) )->get_template_string(), 'NOT DELETED', 'note' ); // NOT DELETED ("note" type)
			$added_notes++;

			$notes = GravityView_Entry_Notes::get_notes( $entry_id );

			$this->assertEquals( sizeof( $notes ), $added_notes );
		}
	}

	/**
	 * Set delete to true
	 * @since 1.15
	 */
	function _set_up_gravityview_settings() {

		$defaults = GravityView_Settings::get_instance()->get_app_settings();

		update_option( 'gravityformsaddon_gravityview_app_settings', $defaults );
	}

	/**
	 * @since 1.15
	 */
	function _set_up_expected_options() {
		update_option( 'gravityformsaddon_gravityview_version', 1 );
		update_option( 'gravityview_cache_blacklist', 1 );

		set_transient( 'gravityview_edd-activate_valid', 1 );
		set_transient( 'gravityview_edd-deactivate_valid', 1 );
		set_transient( 'gravityview_dismissed_notices', 1 );

		set_site_transient( 'gravityview_related_plugins', 1 );
	}

}