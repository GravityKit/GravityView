<?php
/**
 * GravityView Delete Entry Shortcode
 *
 * @since 1.9.2
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Add [gv_edit_entry_link] shortcode
 */
class GravityView_Delete_Entry_Shortcode {

    function __construct() {
        $this->add_hooks();
    }

    function add_hooks() {
	    add_shortcode( 'gv_delete_entry_link', array( $this, 'shortcode' ) );
    }

	/**
	 * Fetch the entry for the View
	 *
	 * We need to use this instead of GFAPI::get_entry() because we want to also pass the Form ID to the
	 * get_entries() method.
	 *
	 * @param int $entry_id
	 * @param int $form_id
	 *
	 * @return array|bool False if no entry exists; Entry array if exists.
	 */
	function get_entry( $entry_id = 0, $form_id = 0 ) {

		$search_criteria = array(
			'field_filters' => array(
				array(
					'key' => 'id',
					'value' => $entry_id
				)
			),
		);

		$paging  = array(
			'offset' => 0,
			'page_size' => 1
		);

		$entries = GFAPI::get_entries( $form_id, $search_criteria, null, $paging );

		$entry = ( ! is_wp_error( $entries ) && ! empty( $entries[0] ) ) ? $entries[0] : false;

		return $entry;
	}

	/**
	 * @param array $atts {
	 *   @type string $view_id Define the ID for the View where the entry will
	 *   @type string $entry_id ID of the entry to edit. If undefined, uses the current entry ID
	 *   @type string $post_id ID of the base post or page to use for an embedded View
	 *   @type string $link_atts Whether to open Edit Entry link in a new window or the same window
	 *   @type string $return What should the shortcode return: link HTML (`html`) or the URL (`url`). Default: `html`
	 *   @type string $field_values Parameters to pass in to the Edit Entry form to prefill data. Uses the same format as Gravity Forms "Allow field to be populated dynamically" {@see https://www.gravityhelp.com/documentation/article/allow-field-to-be-populated-dynamically/ }
	 * }
	 * @param string $content
	 * @param string $context
	 *
	 * @return string|void
	 */
	public function shortcode( $atts = array(), $content = '', $context = 'gv_edit_entry' ) {

		// Make sure GV is loaded
		if( !class_exists('GravityView_frontend') || !class_exists('GravityView_View') ) {
			return null;
		}

		$defaults = array(
			'view_id'      => 0,
			'entry_id'     => 0,
			'post_id'      => 0,
			'link_atts'    => '',
			'return'       => 'html',
			'field_values' => '',
		);

		$settings = shortcode_atts( $defaults, $atts, $context );

		if( empty( $settings['view_id'] ) ) {
			$view_id = GravityView_View::getInstance()->getViewId();
		} else {
			$view_id = absint( $settings['view_id'] );
		}

		if( empty( $view_id ) ) {
			do_action( 'gravityview_log_debug', __METHOD__ . ' A View ID was not defined' );
			return null;
		}

		$post_id = empty( $settings['post_id'] ) ? $view_id : absint( $settings['post_id'] );

		$form_id = gravityview_get_form_id( $view_id );

		$backup_entry_id = GravityView_frontend::getInstance()->getSingleEntry() ? GravityView_frontend::getInstance()->getSingleEntry() : GravityView_View::getInstance()->getCurrentEntry();

		$entry_id = empty( $settings['entry_id'] ) ? $backup_entry_id : absint( $settings['entry_id'] );

		if( empty( $entry_id ) ) {
			do_action( 'gravityview_log_debug', __METHOD__ . ' No entry defined' );
			return null;
		}

		// By default, show only current user
		$user = wp_get_current_user();

		if( ! $user ) {
			do_action( 'gravityview_log_debug', __METHOD__ . ' No user defined; edit entry requires logged in user' );
			return null;
		}

		$entry = $this->get_entry( $entry_id, $form_id );

		// No search results
		if( false === $entry ) {
			do_action( 'gravityview_log_debug', __METHOD__ . ' No entries match the entry ID defined', $entry_id );
			return null;
		}

		// Check permissions
		if( false === GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_id ) ) {
			do_action( 'gravityview_log_debug', __METHOD__ . ' User does not have the capability to edit this entry: ' . $entry_id );
			return null;
		}

		$href = GravityView_Delete_Entry::get_delete_link( $entry, $view_id, $post_id, $settings );

		// Get just the URL, not the tag
		if( 'url' === $settings['return'] ) {
			return $href;
		}

		$link_text = empty( $content ) ? __('Delete Entry', 'gravityview') : $content;

		return gravityview_get_link( $href, $link_text, $settings['link_atts'] );

	}

} //end class

new GravityView_Delete_Entry_Shortcode;
