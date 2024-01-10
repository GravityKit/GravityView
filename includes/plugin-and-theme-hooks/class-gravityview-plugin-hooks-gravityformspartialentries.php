<?php
/**
 * Add Gravity Forms Partial Entries customizations
 *
 * @file      class-gravityview-plugin-hooks-gravityformspartialentries.php
 * @package   GravityView
 * @license   GPL2
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 1.17
 */

/**
 * @inheritDoc
 * @since 2.8
 */
class GravityView_Plugin_Hooks_Gravity_Forms_Partial_Entries extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 2.8
	 */
	protected $constant_name = 'GF_PARTIAL_ENTRIES_VERSION';

	/**
	 * @since 2.8
	 */
	protected function add_hooks() {

		// Don't show "Please note that your information is saved on our server as you enter it." message.
		add_action(
			'gravityview/edit-entry/render/before',
			function () {
				add_filter( 'gform_partialentries_warning_message', '__return_empty_string' );
			}
		);

		add_action(
			'gravityview/edit-entry/render/after',
			function () {
				remove_filter( 'gform_partialentries_warning_message', '__return_empty_string' );
			}
		);

		add_action( 'gravityview/edit_entry/after_update', array( $this, 'maybe_save_partial_entry' ), 10, 3 );

		parent::add_hooks();
	}

	/**
	 * Update the Partial Entries progress after an entry is updated in Edit Entry
	 *
	 * @since 2.8
	 *
	 * @param array                         $form Gravity Forms form array
	 * @param string                        $entry_id Numeric ID of the entry that was updated
	 * @param GravityView_Edit_Entry_Render $edit_entry_render This object
	 *
	 * @return void
	 */
	function maybe_save_partial_entry( $form, $entry_id, $edit_entry_render ) {

		if ( ! class_exists( 'GF_Partial_Entries' ) ) {
			return;
		}

		$partial_entries_addon = GF_Partial_Entries::get_instance();

		$feed_settings = $partial_entries_addon->get_feed_settings( $form['id'] );

		$is_enabled = \GV\Utils::get( $feed_settings, 'enable', 0 );

		if ( ! $is_enabled ) {
			return;
		}

		$entry = $edit_entry_render->get_entry();

		$partial_entry_id = \GV\Utils::get( $entry, 'partial_entry_id' );

		if ( empty( $partial_entry_id ) ) {
			return;
		}

		// Set the expected $_POST key for the Add-On to use
		$_POST['partial_entry_id'] = $partial_entry_id;

		gravityview()->log->debug(
			'Saving partial entry (ID #{partial_entry_id}) for Entry #{entry_id}',
			array(
				'partial_entry_id' => $partial_entry_id,
				'entry_id'         => $entry_id,
			)
		);

		$partial_entries_addon->maybe_save_partial_entry( $form['id'] );
	}
}

new GravityView_Plugin_Hooks_Gravity_Forms_Partial_Entries();
