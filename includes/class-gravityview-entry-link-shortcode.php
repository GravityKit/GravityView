<?php

/**
 * Handle the [gv_entry_link] shortcode
 *
 * Replaces [gv_edit_entry_link] and [gv_delete_entry_link] shortcodes
 *
 * @since 1.15
 */
class GravityView_Entry_Link_Shortcode {

	/**
	 * @type array Entry fetched using the $atts['entry_id'] shortcode setting.
	 * @since 1.15
	 */
	private $entry = array();

	/**
	 * @type int If set, generate a link to the entry for this View ID. Required when used outside a View. Otherwise, current View ID is used.
	 * @since 1.15
	 */
	private $view_id = 0;

	/**
	 * @type array The accepted shortcode attribute pairs, with defaults set
	 * @since 1.15
	 */
	private static $defaults = array(
		'action'       => 'read',
		'view_id'      => 0,
		'entry_id'     => 0,
		'post_id'      => 0,
		'link_atts'    => '',
		'return'       => 'html',
		'field_values' => '',
		'lightbox'     => false,
	);

	/**
	 * @type array The final settings for the shortcode, after merging passed $atts with self::$defaults
	 * @since 1.15
	 */
	private $settings = array();

	function __construct() {
		$this->add_hooks();
	}

	/**
	 * Add shortcodes
	 *
	 * @since 1.15
	 */
	private function add_hooks() {
		add_shortcode( 'gv_entry_link', array( $this, 'read_shortcode' ) );
		add_shortcode( 'gv_edit_entry_link', array( $this, 'edit_shortcode' ) );
		add_shortcode( 'gv_delete_entry_link', array( $this, 'delete_shortcode' ) );
	}

	/**
	 * @since 1.15
	 * @copydoc GravityView_Entry_Link_Shortcode::shortcode
	 */
	public function read_shortcode( $atts, $content = null, $context = 'gv_entry_link' ) {
		return $this->shortcode( $atts, $content, $context );
	}

	/**
	 * Backward compatibility for existing `gv_edit_entry_link` shortcode
	 * Forces $atts['action'] to "edit"
	 *
	 * @since 1.15
	 * @copydoc GravityView_Entry_Link_Shortcode::shortcode
	 */
	public function edit_shortcode( $atts = array(), $content = null, $context = 'gv_edit_entry_link' ) {

		$atts = shortcode_atts( self::$defaults, $atts );

		$atts['action'] = 'edit';

		return $this->shortcode( $atts, $content, $context );
	}

	/**
	 * Backward compatibility for existing `gv_delete_entry_link` shortcodes
	 * Forces $atts['action'] to "delete"
	 *
	 * @since 1.15
	 * @copydoc GravityView_Entry_Link_Shortcode::shortcode
	 */
	public function delete_shortcode( $atts = array(), $content = null, $context = 'gv_delete_entry_link' ) {

		$atts = shortcode_atts( self::$defaults, $atts );

		$atts['action'] = 'delete';

		return $this->shortcode( $atts, $content, $context );
	}

	/**
	 * Generate a link to an entry. The link can be an edit, delete, or standard link.
	 *
	 * @since 1.15
	 *
	 * @param array       $atts {
	 *    @type string $action What type of link to generate. Options: `read`, `edit`, and `delete`. Default: `read`
	 *    @type string $view_id Define the ID for the View. If not set, use current View ID, if exists.
	 *    @type string $entry_id ID of the entry to edit. If undefined, uses the current entry ID, if exists.
	 *    @type string $post_id ID of the base post or page to use for an embedded View
	 *    @type string $link_atts Pass anchor tag attributes (`target=_blank` to open Edit Entry link in a new window, for example)
	 *    @type bool   $lightbox When true, opens the entry link in a lightbox/modal with iframe. Default: false
	 *    @type string $return What should the shortcode return: link HTML (`html`) or the URL (`url`). Default: `html`
	 *    @type string $field_values Only used for `action="edit"`. Parameters to pass in to the prefill data in Edit Entry form. Uses the same format as Gravity Forms "Allow field to be populated dynamically" {@see https://www.gravityhelp.com/documentation/article/allow-field-to-be-populated-dynamically/ }
	 * }
	 *
	 * @param string|null $content Used as link anchor text, if specified.
	 * @param string      $context Current shortcode being called. Not used.
	 *
	 * @return null|string If admin or an error occurred, returns null. Otherwise, returns entry link output. If `$atts['return']` is 'url', the entry link URL. Otherwise, entry link `<a>` HTML tag.
	 */
	private function shortcode( $atts, $content = null, $context = 'gv_entry_link' ) {
		// Don't process when saving post. Keep processing if it's admin-ajax.php
		if ( gravityview()->request->is_admin() ) {
			return null;
		}

		// Make sure GV is loaded
		if ( ! class_exists( 'GravityView_frontend' ) || ! class_exists( 'GravityView_View' ) ) {
			gravityview()->log->error( 'GravityView_frontend or GravityView_View do not exist.' );

			return null;
		}

		$atts = gv_map_deep( $atts, [ 'GravityView_Merge_Tags', 'replace_get_variables' ] );

		$this->settings = shortcode_atts( self::$defaults, $atts, $context );

		$this->view_id = empty( $this->settings['view_id'] ) ? GravityView_View::getInstance()->getViewId() : absint( $this->settings['view_id'] );

		if ( empty( $this->view_id ) && get_the_ID() ) {
			$this->view_id = get_the_ID();
		}

		if ( empty( $this->view_id ) ) {
			gravityview()->log->error( 'A View ID was not defined and we are not inside a View' );

			return null;
		}

		$this->entry = $this->get_entry( $this->settings['entry_id'] );

		if ( empty( $this->entry ) ) {
			gravityview()->log->error( 'An Entry ID was not defined or found. Entry ID: {entry_id}', array( 'entry_id' => $this->settings['entry_id'] ) );

			return null;
		}

		gravityview()->log->debug(
			'{context} atts:',
			array(
				'context' => $context,
				'data'    => $atts,
			)
		);

		if ( ! $this->has_cap() ) {
			gravityview()->log->error(
				'User does not have the capability to {action} this entry: {entry_id}',
				array(
					'action'   => esc_attr( $this->settings['action'] ),
					'entry_id' => $this->entry['id'],
				)
			);

			return null;
		}

		$url = $this->get_url();

		if ( ! $url ) {
			gravityview()->log->error( 'Link returned false; View or Post may not exist.' );

			return false;
		}

		// Get just the URL, not the tag
		if ( 'url' === $this->settings['return'] ) {
			return $url;
		}

		$link_atts = $this->get_link_atts();

		$link_text = $this->get_anchor_text( $content );

		$return = gravityview_get_link( $url, $link_text, $link_atts );

		/**
		 * Modify the output of the [gv_entry_link] shortcode.
		 *
		 * @since 2.0.15
		 * @param string $return The HTML link output
		 * @param array {
		 *   @type string        $url The URL used to generate the anchor tag. {@see GravityView_Entry_Link_Shortcode::get_url}
		 *   @type string        $link_text {@see GravityView_Entry_Link_Shortcode::get_anchor_text}
		 *   @type array         $link_atts {@see GravityView_Entry_Link_Shortcode::get_link_atts}
		 *   @type array|string  $atts Shortcode atts passed to shortcode
		 *   @type string        $content Content passed to shortcode
		 *   @type string        $context The tag of the shortcode being called
		 * }
		 */
		$return = apply_filters( 'gravityview/shortcodes/gv_entry_link/output', $return, compact( 'url', 'link_text', 'link_atts', 'atts', 'content', 'context' ) );

		return $return;
	}

	/**
	 * Parse shortcode atts to fetch `link_atts`, which will be added to the output of the HTML anchor tag generated by shortcode
	 * Only used when `return` value of shortcode is not "url"
	 *
	 * @since 1.15
	 * @see gravityview_get_link() See acceptable attributes here
	 * @return array Array of attributes to be added
	 */
	private function get_link_atts() {

		wp_parse_str( $this->settings['link_atts'], $link_atts );

		if ( 'delete' === $this->settings['action'] ) {
			$link_atts['onclick'] = isset( $link_atts['onclick'] ) ? $link_atts['onclick'] : GravityView_Delete_Entry::get_confirm_dialog();
		}

		return (array) $link_atts;
	}

	/**
	 * Get the anchor text for the link. If content inside shortcode is defined, use that as the text. Otherwise, use default values.
	 *
	 * Only used when `return` value of shortcode is not "url"
	 *
	 * @since 1.15
	 *
	 * @param string|null $content Content inside shortcode, if defined
	 *
	 * @return string Text to use for HTML anchor
	 */
	private function get_anchor_text( $content = null ) {

		if ( $content ) {
			return do_shortcode( $content );
		}

		switch ( $this->settings['action'] ) {
			case 'edit':
				$anchor_text = __( 'Edit Entry', 'gk-gravityview' );
				break;
			case 'delete':
				$anchor_text = __( 'Delete Entry', 'gk-gravityview' );
				break;
			default:
				$anchor_text = __( 'View Details', 'gk-gravityview' );
		}

		return $anchor_text;
	}

	/**
	 * Get the URL for the entry.
	 *
	 * Uses the `post_id`, `view_id` params as defined in the shortcode attributes.
	 *
	 * @since 1.15
	 *
	 * @param string|null $content Content inside shortcode, if defined
	 *
	 * @return string|boolean If URL is fetched, the URL to the entry link. If not found, returns false.
	 */
	private function get_url() {

		// if post_id is not defined, default to view_id
		$post_id = empty( $this->settings['post_id'] ) ? $this->view_id : $this->settings['post_id'];

		switch ( $this->settings['action'] ) {
			case 'edit':
				$url = GravityView_Edit_Entry::get_edit_link( $this->entry, $this->view_id, $post_id );
				break;
			case 'delete':
				$url = GravityView_Delete_Entry::get_delete_link( $this->entry, $this->view_id, $post_id );
				break;
			case 'read':
			default:
				$url = GravityView_API::entry_link( $this->entry, $post_id );
		}

		$url = $this->maybe_add_field_values_query_args( $url );

		return $url;
	}

	/**
	 * Check whether the user has the capability to see the shortcode output, depending on the action ('read', 'edit', 'delete')
	 *
	 * @since 1.15
	 * @return bool True: has cap.
	 */
	private function has_cap() {

		switch ( $this->settings['action'] ) {
			case 'edit':
				$has_cap = GravityView_Edit_Entry::check_user_cap_edit_entry( $this->entry, $this->view_id );
				break;
			case 'delete':
				$has_cap = GravityView_Delete_Entry::check_user_cap_delete_entry( $this->entry, array(), $this->view_id );
				break;
			case 'read':
			default:
				$has_cap = true; // TODO: add cap check for read_gravityview
		}

		return $has_cap;
	}

	/**
	 * Get entry array from `entry_id` parameter. If no $entry_id
	 *
	 * @since 1.15
	 * @uses GVCommon::get_entry
	 * @uses GravityView_frontend::getSingleEntry
	 *
	 * @param int|string $entry_id Gravity Forms Entry ID. If not passed, current View's current entry ID will be used, if found.
	 *
	 * @return array|bool Gravity Forms array, if found. Otherwise, false.
	 */
	private function get_entry( $entry_id = 0 ) {
		static $entries = array();

		if ( empty( $entry_id ) ) {
			$backup_entry = GravityView_frontend::getInstance()->getSingleEntry() ? GravityView_frontend::getInstance()->getEntry() : GravityView_View::getInstance()->getCurrentEntry();

			if ( ! $backup_entry ) {
				gravityview()->log->error( 'No entry defined (or entry id not valid number)', array( 'data' => $this->settings ) );

				return false;
			}

			$entry = $backup_entry;
		} elseif ( in_array( $entry_id, array( 'first', 'last' ) ) ) {
			$view = GV\View::by_id( $this->view_id );

			if ( ! $view ) {
				gravityview()->log->error( "A View with ID {$this->view_id} was not found." );

				return false;
			}

			if ( ! isset( $entry[ $entry_id ] ) ) {
				$entry = 'last' === $entry_id ? $view->get_entries( null )->first() : $view->get_entries( null )->last();
			}

			if ( $entry ) {
				$entry = $entry->as_entry();
			}
		} else {
			$entry = isset( $entries[ $entry_id ] ) ? $entries[ $entry_id ] : GVCommon::get_entry( $entry_id, true, false );
		}

		if ( $entry ) {
			$entries[ $entry_id ] = $entry;
		} else {
			// No search results
			gravityview()->log->error( 'No entries match the entry ID defined: {entry_id}', array( 'entry_id' => $entry_id ) );

			return false;
		}

		return $entry;
	}

	/**
	 * Allow passing URL params to dynamically populate the Edit Entry form
	 * If `field_values` key is set, run it through `parse_str()` and add the values to $url
	 *
	 * @since 1.15
	 *
	 * @param string $href URL
	 */
	private function maybe_add_field_values_query_args( $url ) {

		if ( $url && ! empty( $this->settings['field_values'] ) ) {

			wp_parse_str( $this->settings['field_values'], $field_values );

			$url = add_query_arg( $field_values, $url );
		}

		return $url;
	}
}

new GravityView_Entry_Link_Shortcode();
