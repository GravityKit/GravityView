<?php
/**
 * The GravityView Delete Entry Extension
 *
 * Delete entries in GravityView.
 *
 * @since     1.5.1
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * @since 1.5.1
 */
final class GravityView_Delete_Entry {

	static $file;
	static $instance;
	var $entry;
	var $form;
	var $view_id;
	var $is_valid = null;

	/**
	 * Component instances.
	 *
	 * @var array
	 * @since 2.9.2
	 */
	public $instances = array();

	/**
	 * The value of the `delete_redirect` option when the setting is to redirect to Multiple Entries after delete
	 *
	 * @since 2.9.2
	 */
	const REDIRECT_TO_MULTIPLE_ENTRIES_VALUE = 1;

	/**
	 * The value of the `delete_redirect` option when the setting is to redirect to URL
	 *
	 * @since 2.9.2
	 */
	const REDIRECT_TO_URL_VALUE = 2;

	function __construct() {

		self::$file = plugin_dir_path( __FILE__ );

		if ( is_admin() ) {
			$this->load_components( 'admin' );
		}

		require_once trailingslashit( self::$file ) . 'class-gravityview-field-delete-link.php';

		$this->add_hooks();
	}

	/**
	 * Load other files related to Delete Entry functionality
	 *
	 * @since 2.9.2
	 *
	 * @param $component
	 */
	private function load_components( $component ) {

		$dir = trailingslashit( self::$file );

		$filename  = $dir . 'class-delete-entry-' . $component . '.php';
		$classname = 'GravityView_Delete_Entry_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $component ) ) );

		// Loads component and pass extension's instance so that component can talk each other.
		require_once $filename;

		$this->instances[ $component ] = new $classname( $this );
		$this->instances[ $component ]->load();
	}

	/**
	 * @since 1.9.2
	 */
	private function add_hooks() {

		add_action( 'wp', array( $this, 'process_delete' ), 10000 );

		add_action( 'gravityview_before', array( $this, 'maybe_display_message' ) );

		// add template path to check for field
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

		add_action( 'gravityview/edit-entry/publishing-action/after', array( $this, 'add_delete_button' ), 10, 4 );

		add_action( 'gravityview/delete-entry/deleted', array( $this, 'process_connected_posts' ), 10, 2 );
		add_action( 'gravityview/delete-entry/trashed', array( $this, 'process_connected_posts' ), 10, 2 );

		add_filter( 'gravityview/field/is_visible', array( $this, 'maybe_not_visible' ), 10, 3 );

		add_filter( 'gravityview/api/reserved_query_args', array( $this, 'add_reserved_arg' ) );

		add_filter( 'gform_notification_events', [ $this, 'add_delete_notification_events' ], 10, 2 );
		add_action( 'gravityview/delete-entry/trashed', [ $this, 'trigger_notifications' ], 10, 2 );
		add_action( 'gravityview/delete-entry/deleted', [ $this, 'trigger_notifications' ], 10, 2 );
	}

	/**
	 * Adds "delete" to the list of internal reserved query args
	 *
	 * @since 2.10
	 *
	 * @param array $args Existing reserved args
	 *
	 * @return array
	 */
	public function add_reserved_arg( $args ) {

		$args[] = 'delete';

		return $args;
	}

	/**
	 * Return the instantiated class object
	 *
	 * @since  1.5.1
	 * @return GravityView_Delete_Entry
	 */
	static function getInstance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hide the field or not.
	 *
	 * For non-logged in users.
	 * For users that have no delete rights on any of the current entries.
	 *
	 * @param bool      $visible Visible or not.
	 * @param \GV\Field $field The field.
	 * @param \GV\View  $view The View context.
	 *
	 * @return bool
	 */
	public function maybe_not_visible( $visible, $field, $view ) {
		if ( 'delete_link' !== $field->ID ) {
			return $visible;
		}

		if ( ! $view ) {
			return $visible;
		}

		static $visibility_cache_for_view = array();

		$anchor_id = $view->get_anchor_id();

		if ( ! is_null( $result = \GV\Utils::get( $visibility_cache_for_view, $anchor_id, null ) ) ) {
			return $result;
		}

		foreach ( $view->get_entries()->all() as $entry ) {
			if ( self::check_user_cap_delete_entry( $entry->as_entry(), $field->as_configuration(), $view ) ) {
				// At least one entry is deletable for this user
				$visibility_cache_for_view[ $anchor_id ] = true;
				return true;
			}
		}

		$visibility_cache_for_view[ $anchor_id ] = false;

		return false;
	}

	/**
	 * Include this extension templates path
	 *
	 * @since  1.5.1
	 * @param array $file_paths List of template paths ordered
	 */
	function add_template_path( $file_paths ) {

		// Index 100 is the default GravityView template path.
		// Index 110 is Edit Entry link
		$file_paths[115] = self::$file;

		return $file_paths;
	}

	/**
	 * Make sure there's an entry
	 *
	 * @since 1.5.1
	 * @param [type] $entry [description]
	 */
	function set_entry( $entry = null ) {
		_deprecated_function( __METHOD__, '2.9.2' );
	}

	/**
	 * Generate a consistent nonce key based on the Entry ID
	 *
	 * @since 1.5.1
	 * @param  int $entry_id Entry ID
	 * @return string           Key used to validate request
	 */
	public static function get_nonce_key( $entry_id ) {
		return sprintf( 'delete_%s', $entry_id );
	}

	/**
	 * Generate a nonce link with the base URL of the current View embed
	 *
	 * We don't want to link to the single entry, because when deleted, there would be nothing to return to.
	 *
	 * @since 1.5.1
	 * @param  array $entry Gravity Forms entry array
	 * @param  int   $view_id The View id. Not optional since 2.0
	 * @return string|null If directory link is valid, the URL to process the delete request. Otherwise, `NULL`.
	 */
	public static function get_delete_link( $entry, $view_id = 0, $post_id = null ) {
		if ( ! $view_id ) {
			/** @deprecated path */
			$view_id = gravityview_get_view_id();
		}

		$base = GravityView_API::directory_link( $post_id ?: $view_id, true );

		if ( empty( $base ) ) {
			gravityview()->log->error( 'Post ID does not exist: {post_id}', array( 'post_id' => $post_id ) );

			return null;
		}

		$gv_entry = \GV\GF_Entry::from_entry( $entry );

		// Use the slug instead of the ID for consistent security
		$entry_slug = $gv_entry->get_slug();

		/**
		 * Modify whether to include passed $_GET parameters to the end of the url.
		 *
		 * @since 2.10
		 * @param bool $add_query_params Whether to include passed $_GET parameters to the end of the Delete Link URL. Default: true.
		 */
		$add_query_args = apply_filters( 'gravityview/delete-entry/add_query_args', true );

		if ( $add_query_args ) {
			$base = add_query_arg( gv_get_query_args(), $base );
		}

		$actionurl = add_query_arg(
			array(
				'action'   => 'delete',
				'entry_id' => $entry_slug,
				'gvid'     => $view_id,
				'view_id'  => $view_id,
			),
			remove_query_arg( 'message', $base )
		);

		$url = wp_nonce_url( $actionurl, 'delete_' . $entry_slug, 'delete' );

		return $url;
	}


	/**
	 * Add a Delete button to the "#publishing-action" section of the Delete Entry form
	 *
	 * @since 1.5.1
	 * @since 2.0.13 Added $post_id
	 *
	 * @param array $form    Gravity Forms form array
	 * @param array $entry   Gravity Forms entry array
	 * @param int   $view_id GravityView View ID
	 * @param int   $post_id Current post ID. May be same as View ID.
	 *
	 * @return void
	 */
	public function add_delete_button( $form = array(), $entry = array(), $view_id = null, $post_id = null ) {

		// Only show the link to those who are allowed to see it.
		if ( ! self::check_user_cap_delete_entry( $entry, array(), $view_id ) ) {
			return;
		}

		/**
		 * Should the Delete button be shown in the Edit Entry screen?
		 *
		 * @param boolean $show_delete_button Default: true.
		 */
		$show_delete_button = apply_filters( 'gravityview/delete-entry/show-delete-button', true );

		/**
		 * Should the Delete button be shown in the Edit Entry screen?
		 *
		 * Receives the value of the `gravityview/delete-entry/show-delete-button` filter (default: true).
		 *
		 * @since 2.48.4
		 *
		 * @param boolean $show_delete_button Whether the Delete button should be shown. Default: true.
		 * @param array $form The Gravity Forms form.
		 * @param array $entry The Gravity Forms entry.
		 * @param int $view_id The current View ID.
		 * @param int $post_id The current Post ID. May be same as View ID.
		 */
		$show_delete_button = apply_filters( 'gk/gravityview/delete-entry/show-delete-button', $show_delete_button, $form, $entry, $view_id, $post_id );

		// If the button is hidden by the filter, don't show.
		if ( ! $show_delete_button ) {
			return;
		}

		$attributes = array(
			'class'    => 'btn btn-sm button button-small alignright pull-right btn-danger gv-button-delete',
			'tabindex' => ( GFCommon::$tab_index++ ),
			'onclick'  => self::get_confirm_dialog(),
		);

		$View = \GV\View::by_id( $view_id );

		$delete_label = __( 'Delete', 'Button label to delete an entry from the Edit Entry screen', 'gk-gravityview' );

		if ( $View ) {
			$delete_label = $View->settings->get( 'action_label_delete', $delete_label );
		}

		$delete_label = GFCommon::replace_variables( $delete_label, $form, $entry );

		echo gravityview_get_link( self::get_delete_link( $entry, $view_id, $post_id ), esc_html( $delete_label ), $attributes );
	}

	/**
	 * Handle the deletion request, if $_GET['action'] is set to "delete"
	 *
	 * 1. Check referrer validity
	 * 2. Make sure there's an entry with the slug of $_GET['entry_id']
	 * 3. If so, attempt to delete the entry. If not, set the error status
	 * 4. Remove `action=delete` from the URL
	 * 5. Redirect to the page using `wp_redirect()`
	 *
	 * @since 1.5.1
	 * @uses wp_redirect()
	 * @return void
	 */
	public function process_delete() {

		/* Unslash and Parse $_GET array. */
		$get_fields = wp_parse_args(
			wp_unslash( $_GET ),
			array(
				'action'   => '',
				'entry_id' => '',
				'gvid'     => '',
				'view_id'  => '',
				'delete'   => '',
			)
		);

		// If the form is not submitted, return early
		if ( 'delete' !== $get_fields['action'] || empty( $get_fields['entry_id'] ) ) {
			return;
		}

		// Make sure it's a GravityView request
		$valid_nonce_key = wp_verify_nonce( $get_fields['delete'], self::get_nonce_key( $get_fields['entry_id'] ) );

		if ( ! $valid_nonce_key ) {
			gravityview()->log->debug( 'Delete entry not processed: nonce validation failed.' );

			return;
		}

		// Get the entry slug
		$entry_slug = esc_attr( $get_fields['entry_id'] );

		// Redirect after deleting the entry.
		$view = \GV\View::by_id( $get_fields['view_id'] );

		// See if there's an entry there
		$entry = gravityview_get_entry( $entry_slug, true, false, $view );

		$delete_redirect_base = esc_url_raw( remove_query_arg( array( 'action', 'gvid', 'entry_id' ) ) );

		if ( ! $entry ) {

			gravityview()->log->debug( 'Delete entry failed: there was no entry with the entry slug {entry_slug}', array( 'entry_slug' => $entry_slug ) );

			return $this->_redirect_and_exit( $delete_redirect_base, __( 'The entry does not exist.', 'gk-gravityview' ), 'error' );
		}

		$has_permission = $this->user_can_delete_entry( $entry, \GV\Utils::_GET( 'gvid', \GV\Utils::_GET( 'view_id' ) ) );

		if ( is_wp_error( $has_permission ) ) {
			return $this->_redirect_and_exit( $delete_redirect_base, $has_permission->get_error_message(), 'error' );
		}

		// Delete the entry
		$delete_response = $this->delete_or_trash_entry( $entry, $view ? $view->ID : null );

		if ( is_wp_error( $delete_response ) ) {
			return $this->_redirect_and_exit( $delete_redirect_base, $delete_response->get_error_message(), 'error' );
		}

		if ( self::REDIRECT_TO_URL_VALUE === (int) $view->settings->get( 'delete_redirect' ) ) {

			$form                 = GVCommon::get_form( $entry['form_id'] );
			$redirect_url_setting = $view->settings->get( 'delete_redirect_url' );
			$redirect_url         = GFCommon::replace_variables( $redirect_url_setting, $form, $entry, false, false, false, 'text' );

			return $this->_redirect_and_exit( $redirect_url, '', '', false );
		}

		// Redirect to multiple entries
		return $this->_redirect_and_exit( $delete_redirect_base, '', $delete_response, true );
	}

	/**
	 * Redirects the user to a URL and exits.
	 *
	 * @since 2.9.2
	 *
	 * @param string $url The URL to redirect to.
	 * @param string $message Message to pass through URL.
	 * @param string $status The deletion status ("deleted", "trashed", or "error").
	 * @param bool   $safe_redirect Whether to use wp_safe_redirect() or not.
	 */
	private function _redirect_and_exit( $url, $message = '', $status = '', $safe_redirect = true ) {
		if ( ! apply_filters( 'wp_redirect', $url, 302 ) ) {
			return;
		}

		$delete_redirect_args = array(
			'status'  => $status,
			'message' => $message,
		);

		$delete_redirect_args = array_filter( $delete_redirect_args );

		/**
		 * Modify the query args added to the delete entry redirect.
		 *
		 * @since 2.9.2
		 *
		 * @param array $delete_redirect_args Array with `_delete_nonce`, `message` and `status` keys
		 */
		$delete_redirect_args = apply_filters( 'gravityview/delete-entry/redirect-args', $delete_redirect_args );

		$delete_redirect_url = add_query_arg( $delete_redirect_args, $url );

		if ( $safe_redirect ) {
			wp_safe_redirect( $delete_redirect_url );
		} else {
			wp_redirect( $delete_redirect_url );
		}

		exit();
	}

	/**
	 * Delete mode: permanently delete, or move to trash?
	 *
	 * @since 2.48.5 Added $entry and $view_id parameters.
	 *
	 * @param array $entry The entry to get the delete mode for.
	 * @param int|null $view_id The View ID. Default: null.
	 *
	 * @return string `delete` or `trash`
	 */
	private function get_delete_mode( $entry, $view_id = null ) {

		/**
		 * Delete mode: permanently delete, or move to trash?
		 *
		 * @deprecated TODO Use `gk/gravityview/delete-entry/mode` filter instead.
		 * @since 1.13.1
		 * @param string $delete_mode Delete mode: `trash` or `delete`. Default: `delete`.
		 */
		$delete_mode = apply_filters( 'gravityview/delete-entry/mode', 'delete' );

		/**
		 * Delete mode: permanently delete, or move to trash?
		 *
		 * Receives the value of the deprecated `gravityview/delete-entry/mode` filter (default: `delete`).
		 *
		 * @since 2.48.5
		 *
		 * @link https://docs.gravitykit.com/article/299-change-the-delete-entry-mode-from-delete-to-trash for examples.
		 *
		 * @param string   $delete_mode Delete mode: `trash` or `delete`. Default: `delete`.
		 * @param array $entry The entry to get the delete mode for.
		 * @param int|null $view_id The View ID. Default: null.
		 */
		$delete_mode = apply_filters( 'gk/gravityview/delete-entry/mode', $delete_mode, $entry, $view_id );

		return ( 'trash' === $delete_mode ) ? 'trash' : 'delete';
	}

	/**
	 * Delete or trash an entry.
	 *
	 * @since 1.13.1
	 * @since 2.48.5 Added $view_id parameter.
	 *
	 * @uses GFAPI::delete_entry()
	 * @uses GFAPI::update_entry_property()
	 *
	 * @param array $entry The entry to delete or trash.
	 * @param int   $view_id The View ID. Default: null.
	 *
	 * @return WP_Error|string "deleted" or "trashed" if successful, WP_Error if GFAPI::delete_entry() or updating entry failed.
	 */
	private function delete_or_trash_entry( $entry, $view_id = null ) {

		$entry_id = $entry['id'];

		$mode = $this->get_delete_mode( $entry, $view_id );

		if ( 'delete' === $mode ) {

			gravityview()->log->debug( 'Starting delete entry: {entry_id}', array( 'entry_id' => $entry_id ) );

			// Delete the entry
			$delete_response = GFAPI::delete_entry( $entry_id );

			if ( ! is_wp_error( $delete_response ) ) {
				$delete_response = 'deleted';

				/**
				 * Triggered when an entry is deleted.
				 *
				 * @deprecated TODO Use `gk/gravityview/delete-entry/deleted` action instead.
				 * @since 1.16.4
				 * @param  int $entry_id ID of the Gravity Forms entry
				 * @param  array $entry Deleted entry array
				 */
				do_action( 'gravityview/delete-entry/deleted', $entry_id, $entry );

				/**
				 * Triggered when an entry is deleted.
				 *
				 * @since 2.48.5
				 * @param  int $entry_id ID of the Gravity Forms entry
				 * @param  array $entry Deleted entry array
				 * @param int|null $view_id The View ID. Default: null.
				*/
				do_action( 'gk/gravityview/delete-entry/deleted', $entry_id, $entry, $view_id );
			}

			gravityview()->log->debug( 'Delete response: {delete_response}', array( 'delete_response' => $delete_response ) );

		} else {

			gravityview()->log->debug( 'Starting trash entry: {entry_id}', array( 'entry_id' => $entry_id ) );

			$trashed = GFAPI::update_entry_property( $entry_id, 'status', 'trash' );
			new GravityView_Cache();

			if ( ! $trashed ) {
				$delete_response = new WP_Error( 'trash_entry_failed', __( 'Moving the entry to the trash failed.', 'gk-gravityview' ) );
			} else {

				/**
				 * Triggered when an entry is trashed.
				 *
				 * @deprecated TODO Use `gk/gravityview/delete-entry/trashed` action instead.
				 *
				 * @since  1.16.4
				 *
				 * @param  int $entry_id ID of the Gravity Forms entry.
				 * @param  array $entry Trashed entry array.
				 */
				do_action( 'gravityview/delete-entry/trashed', $entry_id, $entry );

				/**
				 * Triggered when an entry is trashed.
				 *
				 * @since 2.48.5
				 *
				 * @param  int      $entry_id ID of the Gravity Forms entry.
				 * @param  array    $entry Trashed entry array.
				 * @param  int|null $view_id The View ID. Default: null.
				 */
				do_action( 'gk/gravityview/delete-entry/trashed', $entry_id, $entry, $view_id );

				$delete_response = 'trashed';
			}

			gravityview()->log->debug( ' Trashed? {delete_response}', array( 'delete_response' => $delete_response ) );
		}

		return $delete_response;
	}

	/**
	 * Delete or trash a post connected to an entry
	 *
	 * @since 1.17
	 *
	 * @param int   $entry_id ID of entry being deleted/trashed.
	 * @param array $entry Array of the entry being deleted/trashed.
	 */
	public function process_connected_posts( $entry_id = 0, $entry = array() ) {

		// The entry had no connected post
		if ( empty( $entry['post_id'] ) ) {
			return;
		}

		/**
		 * Should posts connected to an entry be deleted when the entry is deleted?
		 * @deprecated TODO Use `gk/gravityview/delete-entry/delete-connected-post` filter instead.
		 * @since 1.17
		 * @param boolean $delete_post If trashing an entry, trash the post. If deleting an entry, delete the post. Default: true
		 */
		$delete_post = apply_filters( 'gravityview/delete-entry/delete-connected-post', true );

		/**
		 * Should posts connected to an entry be deleted when the entry is deleted?
		 *
		 * Receives the value of the deprecated `gravityview/delete-entry/delete-connected-post` filter (default: true).
		 *
		 * @since 2.48.5
		 *
		 * @param boolean $delete_post If trashing an entry, trash the post. If deleting an entry, delete the post. Default: true.
		 * @param array $entry Array of the entry being deleted/trashed.
		 */
		$delete_post = apply_filters( 'gk/gravityview/delete-entry/delete-connected-post', $delete_post, $entry );

		if ( false === $delete_post ) {
			return;
		}

		$action = current_action();

		if ( 'gravityview/delete-entry/deleted' === $action ) {
			$result = wp_delete_post( $entry['post_id'], true ); // Force-delete the post.
		} else {
			$result = wp_trash_post( $entry['post_id'] );
		}

		if ( false === $result ) {
			gravityview()->log->error(
				'(called by {action}): Error processing the Post connected to the entry.',
				array(
					'action' => $action,
					'data'   => $entry,
				)
			);
		} else {
			gravityview()->log->debug(
				'(called by {action}): Successfully processed Post connected to the entry.',
				array(
					'action' => $action,
					'data'   => $entry,
				)
			);
		}
	}

	/**
	 * Is the current nonce valid for editing the entry?
	 *
	 * @since 1.5.1
	 * @return boolean
	 */
	public function verify_nonce() {

		// No delete entry request was made
		if ( empty( $_GET['entry_id'] ) || empty( $_GET['delete'] ) ) {
			return false;
		}

		$nonce_key = self::get_nonce_key( $_GET['entry_id'] );

		$valid = wp_verify_nonce( $_GET['delete'], $nonce_key );

		/**
		 * Override Delete Entry nonce validation. Return true to declare nonce valid.
		 *
		 * @since 1.15.2
		 * @see wp_verify_nonce()
		 * @param int|boolean $valid False if invalid; 1 or 2 when nonce was generated
		 * @param string $nonce_key Name of nonce action used in wp\_verify\_nonce. The $\_GET['delete'] value holds the nonce value itself. Default: delete_{entry_id}
		 */
		$valid = apply_filters( 'gravityview/delete-entry/verify_nonce', $valid, $nonce_key );

		return $valid;
	}

	/**
	 * Get the onclick attribute for the confirm dialogs that warns users before they delete an entry
	 *
	 * @since 1.5.1
	 * @return string HTML `onclick` attribute
	 */
	public static function get_confirm_dialog() {

		$confirm = __( 'Are you sure you want to delete this entry? This cannot be undone.', 'gk-gravityview' );

		/**
		 * Modify the Delete Entry Javascript confirmation text.
		 *
		 * @param string $confirm Default: "Are you sure you want to delete this entry? This cannot be undone."
		 */
		$confirm = apply_filters( 'gravityview/delete-entry/confirm-text', $confirm );

		return 'return window.confirm(\'' . esc_js( $confirm ) . '\');';
	}

	/**
	 * Check if the user can edit the entry
	 *
	 * - Is the nonce valid?
	 * - Does the user have the right caps for the entry
	 * - Is the entry in the trash?
	 *
	 * @since 1.5.1
	 * @param  array $entry Gravity Forms entry array
	 * @return boolean|WP_Error        True: can edit form. WP_Error: nope.
	 */
	function user_can_delete_entry( $entry = array(), $view_id = null ) {
		$error = null;

		if ( ! $this->verify_nonce() ) {
			$error = __( 'The link to delete this entry is not valid; it may have expired.', 'gk-gravityview' );
		}

		if ( ! self::check_user_cap_delete_entry( $entry, array(), $view_id ) ) {
			$error = __( 'You do not have permission to delete this entry.', 'gk-gravityview' );
		}

		if ( 'trash' === $entry['status'] ) {
			if ( 'trash' === $this->get_delete_mode( $entry, $view_id ) ) {
				$error = __( 'The entry is already in the trash.', 'gk-gravityview' );
			} else {
				$error = __( 'You cannot delete the entry; it is already in the trash.', 'gk-gravityview' );
			}
		}

		// No errors; everything's fine here!
		if ( empty( $error ) ) {
			return true;
		}

		gravityview()->log->error( '{error}', array( 'erorr' => $error ) );

		return new WP_Error( 'gravityview-delete-entry-permissions', $error );
	}


	/**
	 * checks if user has permissions to view the link or delete a specific entry
	 *
	 * @since 1.5.1
	 * @since 1.15 Added `$view_id` param
	 *
	 * @param  array        $entry Gravity Forms entry array
	 * @param array        $field Field settings (optional)
	 * @param int|\GV\View $view Pass a View ID to check caps against. If not set, check against current View (@deprecated no longer optional)
	 * @return bool
	 */
	public static function check_user_cap_delete_entry( $entry, $field = array(), $view = 0 ) {
		if ( ! $view ) {
			/** @deprecated path */
			$view    = \GV\View::by_id( GravityView_View::getInstance()->getViewId() );
		} elseif ( ! $view instanceof \GV\View ) {
			$view = \GV\View::by_id( $view );
		}

		$current_user = wp_get_current_user();

		$entry_id = isset( $entry['id'] ) ? $entry['id'] : null;

		// Or if they can delete any entries (as defined in Gravity Forms), we're good.
		if ( GVCommon::has_cap( array( 'gravityforms_delete_entries', 'gravityview_delete_others_entries' ), $entry_id ) ) {
			gravityview()->log->debug( 'Current user has `gravityforms_delete_entries` or `gravityview_delete_others_entries` capability.' );

			return true;
		}

		// If field options are passed, check if current user can view the link.
		if ( ! empty( $field ) ) {
			// If capability is not defined, something is not right!
			if ( empty( $field['allow_edit_cap'] ) ) {
				gravityview()->log->error( 'Cannot read delete entry field caps', array( 'data' => $field ) );

				return false;
			}

			// Do not return true if cap is read, as we need to check if the current user created the entry.
			if ( GVCommon::has_cap( $field['allow_edit_cap'] ) && 'read' !== $field['allow_edit_cap'] ) {
				return true;
			}
		}

		if ( ! isset( $entry['created_by'] ) ) {
			gravityview()->log->error( 'Entry property `created_by` doesn\'t exist.' );

			return false;
		}

		$user_delete = $view->settings->get( 'user_delete' );

		// Only checks user_delete view option if view is already set
		if ( $view && empty( $user_delete ) ) {
			gravityview()->log->debug( 'User Delete is disabled. Returning false.' );

			return false;
		}

		// If the logged-in user is the same as the user who created the entry, we're good.
		if ( is_user_logged_in() && $current_user->ID  === (int) $entry['created_by'] ) {
			gravityview()->log->debug( 'User {user_id} created the entry.', array( 'user_id' => $current_user->ID ) );

			return true;
		}

		gravityview()->log->debug( 'User {user_id} is not authorized to view delete entry link ', array( 'user_id' => $current_user->ID ) );

		return false;
	}

	/**
	 * After processing delete entry, the user will be redirected to the referring View or embedded post/page. Display a message on redirection.
	 *
	 * If success, there will be `status` URL parameters `status=>success`
	 * If an error, there will be `status` and `message` URL parameters `status=>error&message=example`
	 *
	 * @since 1.15.2 Only show message when the URL parameter's View ID matches the current View ID
	 * @since 1.5.1
	 *
	 * @param int $current_view_id The ID of the View being rendered
	 * @return void
	 */
	public function maybe_display_message( $current_view_id = 0 ) {

		if ( empty( $_GET['status'] ) || ! self::verify_nonce() ) {
			return;
		}

		// Entry wasn't deleted from current View
		if ( isset( $_GET['view_id'] ) && intval( $_GET['view_id'] ) !== intval( $current_view_id ) ) {
			return;
		}

		$this->display_message();
	}

	public function display_message() {

		if ( empty( $_GET['status'] ) || empty( $_GET['delete'] ) ) {
			return;
		}

		$status           = esc_attr( $_GET['status'] );
		$message_from_url = \GV\Utils::_GET( 'message', '' );
		$message_from_url = rawurldecode( stripslashes_deep( $message_from_url ) );
		$class            = '';

		switch ( $status ) {
			case 'error':
				$class         = ' gv-error error';
				$error_message = __( 'There was an error deleting the entry: %s', 'gk-gravityview' );
				$message       = sprintf( $error_message, $message_from_url );
				break;
			case 'trashed':
				$message = __( 'The entry was successfully moved to the trash.', 'gk-gravityview' );
				break;
			default:
				$message = __( 'The entry was successfully deleted.', 'gk-gravityview' );
				break;
		}

		/**
		 * Modify the Delete Entry messages.
		 *
		 * @since 1.13.1
		 * @param string $message Message to be displayed
		 * @param string $status Message status (`error` or `success`)
		 * @param string $message_from_url The original error message, if any, without the "There was an error deleting the entry:" prefix
		 */
		$message = apply_filters( 'gravityview/delete-entry/message', esc_attr( $message ), $status, $message_from_url );

		echo GVCommon::generate_notice( $message, $class );
	}

	/**
	 * Passes approval notification and action hook to the send_notifications method
	 *
	 * @since    $ver$
	 * @see      GravityView_Entry_Approval::send_notifications()
	 *
	 * @param int   $entry_id ID of entry being updated
	 * @param array $entry    The entry object.
	 */
	public function trigger_notifications( $entry_id = 0, $entry = [] ): void {
		$event = (string) current_action();

		// If the delete mode is set to `trash` still trigger the notification.
		if ( 'gravityview/delete-entry/trashed' === $event ) {
			$event = 'gravityview/delete-entry/deleted';
		}

		GravityView_Notifications::send_notifications( (int) $entry_id, $event, $entry );
	}

	/**
	 * Adds entry deleted status to notification events
	 *
	 * @since $ver$
	 *
	 * @param array $notification_events The notification events.
	 */
	public function add_delete_notification_events( array $notification_events ): array {
		$notification_events['gravityview/delete-entry/deleted'] = 'GravityView - ' . esc_html_x( 'Entry is deleted', 'The title for an event in a notifications drop down list.', 'gk-gravityview' );

		return $notification_events;
	}

} // end class

GravityView_Delete_Entry::getInstance();
