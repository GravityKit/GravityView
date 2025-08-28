<?php
namespace GV\Shortcodes;

use GravityView_API;
use GravityView_Delete_Entry;
use GravityView_Edit_Entry;
use GravityView_frontend;
use GV\View;
use GVCommon;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The [gv_entry_link] shortcode and backward compatibility shortcodes.
 *
 * Handles [gv_entry_link], [gv_edit_entry_link], and [gv_delete_entry_link] shortcodes.
 */
class gv_entry_link extends \GV\Shortcode {
	/**
	 * {@inheritDoc}
	 */
	public $name = 'gv_entry_link';

	/**
	 * {@inheritDoc}
	 */
	protected static $defaults = [
		'action'       => 'read',
		'view_id'      => 0,
		'entry_id'     => 0,
		'post_id'      => 0,
		'link_atts'    => '',
		'return'       => 'html',
		'field_values' => '',
		'lightbox'     => false,
		'secret'       => '',
	];

	/**
	 * @var array Entry fetched using the shortcode settings.
	 */
	protected $entry = array();

	/**
	 * @var int If set, generate a link to the entry for this View ID.
	 */
	protected $view_id = 0;

	/**
	 * @var array The final settings for the shortcode.
	 */
	protected $settings = array();

	/**
	 * {@inheritDoc}
	 */
	public static function add( $name = null ) {
		parent::add( 'gv_entry_link' );
		parent::add( 'gv_edit_entry_link' );
		parent::add( 'gv_delete_entry_link' );
	}

	/**
	 * Generate a link to an entry. The link can be an edit, delete, or standard link.
	 *
	 * @since 1.15
	 * @since TODO Moved to gv_entry_link class.
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
	 * @param string      $tag Current shortcode being called. Not used.
	 *
	 * @return null|string If admin or an error occurred, returns null. Otherwise, returns entry link output. If `$atts['return']` is 'url', the entry link URL. Otherwise, entry link `<a>` HTML tag.
	 */
	public function callback( $atts, $content = '', $tag = '' ) {
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

		// Adjust defaults based on which shortcode was called
		$defaults = self::$defaults;
		if ( 'gv_edit_entry_link' === $tag ) {
			$defaults['action'] = 'edit';
		} elseif ( 'gv_delete_entry_link' === $tag ) {
			$defaults['action'] = 'delete';
		}

		$this->settings = shortcode_atts( $defaults, $atts, $tag );

		$this->view_id = empty( $this->settings['view_id'] ) ? \GravityView_View::getInstance()->getViewId() : absint( $this->settings['view_id'] );

		if ( empty( $this->view_id ) && get_the_ID() ) {
			$this->view_id = get_the_ID();
		}

		if ( empty( $this->view_id ) ) {
			gravityview()->log->error( 'A View ID was not defined and we are not inside a View' );
			return null;
		}

		// Sanity check to make sure the expected View ID is set in the atts.
		$atts['view_id'] = $this->view_id;

		$view = $this->get_view_by_atts( $atts );

		if ( is_wp_error( $view ) ) {
			return $this->handle_error( $view );
		}

		if ( ! $view ) {
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
				'context' => $tag,
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
		 * @since TODO Added $final_atts to the second parameter of the filter.
		 * @param string $return The HTML link output.
		 * @param array {
		 *   @type string        $url The URL used to generate the anchor tag.
		 *   @type string        $link_text The link text.
		 *   @type array         $link_atts The link attributes.
		 *   @type array|string  $atts Shortcode atts passed to shortcode.
		 *   @type string        $content Content passed to shortcode.
		 *   @type string        $context The tag of the shortcode being called.
		 *   @type array         $final_atts The calculated attributes of the shortcode.
		 * }
		 */
		$context = $tag; // For backward compatibility with filter.
		$final_atts = $this->settings;
		$return = apply_filters( 'gravityview/shortcodes/gv_entry_link/output', $return, compact( 'url', 'link_text', 'link_atts', 'atts', 'content', 'context', 'final_atts' ) );

		return $return;
	}

	/**
	 * Parse shortcode atts to fetch `link_atts`, which will be added to the output of the HTML anchor tag.
	 *
	 * Only used when `return` value of shortcode is not "url"
	 *
	 * @return array Array of attributes to be added
	 */
	protected function get_link_atts() {
		wp_parse_str( $this->settings['link_atts'], $link_atts );

		if ( 'delete' === $this->settings['action'] ) {
			$link_atts['onclick'] = isset( $link_atts['onclick'] ) ? $link_atts['onclick'] : GravityView_Delete_Entry::get_confirm_dialog();
		}

		return (array) $link_atts;
	}

	/**
	 * Get the anchor text for the link. If content inside shortcode is defined, use that as the text.
	 *
	 * Only used when `return` value of shortcode is not "url"
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
	 * @return string|boolean If URL is fetched, the URL to the entry link. If not found, returns false.
	 */
	private function get_url() {
		// If post_id is not defined, default to view_id.
		$post_id = empty( $this->settings['post_id'] ) ? $this->view_id : $this->settings['post_id'];

		switch ( $this->settings['action'] ) {
			case 'edit':
				$url = GravityView_Edit_Entry::get_edit_link( $this->entry, $this->view_id, $post_id, $this->settings['field_values'] ?? '' );

				break;
			case 'delete':
				$url = GravityView_Delete_Entry::get_delete_link( $this->entry, $this->view_id, $post_id );

				break;
			case 'read':
			default:
				$url = GravityView_API::entry_link( $this->entry, $post_id, true, $this->view_id );
		}

		return $url;
	}

	/**
	 * Check whether the user has the capability to see the shortcode output, depending on the action.
	 *
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
	 * Get entry array from `entry_id` parameter.
	 *
	 * @param int|string $entry_id Gravity Forms Entry ID. If not passed, current View's current entry ID will be used, if found.
	 *
	 * @return array|bool Gravity Forms array, if found. Otherwise, false.
	 */
	private function get_entry( $entry_id = 0 ) {
		static $entries = array();

		if ( empty( $entry_id ) ) {
			$backup_entry = GravityView_frontend::getInstance()->getSingleEntry() ? GravityView_frontend::getInstance()->getEntry() : \GravityView_View::getInstance()->getCurrentEntry();

			if ( ! $backup_entry ) {
				gravityview()->log->error( 'No entry defined (or entry id not valid number)', array( 'data' => $this->settings ) );

				return false;
			}

			// Do not cache "current" entries keyed by 0; context-dependent and changes per loop.
			return $backup_entry;
		} elseif ( in_array( $entry_id, [ 'first', 'last' ], true ) ) {
			$view = View::by_id( $this->view_id );

			if ( ! $view ) {
				gravityview()->log->error( "A View with ID {$this->view_id} was not found." );

				return false;
			}

			$entry = isset( $entries[ $entry_id ] ) ? $entries[ $entry_id ] : null;

			if ( ! $entry ) {
				$collection = $view->get_entries();
				$entry_obj  = ( 'first' === $entry_id ) ? $collection->first() : $collection->last();
				$entry      = $entry_obj ? $entry_obj->as_entry() : null;
			}
		} else {
			$entry = isset( $entries[ $entry_id ] ) ? $entries[ $entry_id ] : GVCommon::get_entry( $entry_id, true, false );
		}

		if ( $entry ) {
			$entries[ $entry_id ] = $entry;
		} else {
			// No search results.
			gravityview()->log->error( 'No entries match the entry ID defined: {entry_id}', array( 'entry_id' => $entry_id ) );

			return false;
		}

		return $entry;
	}

	/**
	 * Allow passing URL params to dynamically populate the Edit Entry form.
	 *
	 * If `field_values` key is set, run it through `parse_str()` and add the values to $url
	 *
	 * @param string $url URL
	 * @return string Modified URL
	 */
	private function maybe_add_field_values_query_args( $url ) {
		if ( $url && ! empty( $this->settings['field_values'] ) ) {
			wp_parse_str( $this->settings['field_values'], $field_values );
			$url = add_query_arg( $field_values, $url );
		}

		return $url;
	}
}