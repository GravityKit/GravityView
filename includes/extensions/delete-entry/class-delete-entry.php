<?php
/**
 * The GravityView Delete Entry Extension
 *
 * Delete entries in GravityView.
 *
 * @since     1.5.1
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
 * @since 1.5.1
 */
final class GravityView_Delete_Entry {

	static $file;
	static $nonce_key;
	static $instance;
	var $entry;
	var $form;
	var $view_id;
	var $is_valid = NULL;

	function __construct() {

		self::$instance = &$this;

		self::$file = plugin_dir_path( __FILE__ );

		add_action( 'wp', array( $this, 'process_delete' ), 10000 );

		add_filter( 'gravityview_entry_default_fields', array( $this, 'add_default_field'), 10, 3 );

		add_action( 'gravityview_before', array( $this, 'display_message' ) );

		// For the Edit Entry Link, you don't want visible to all users.
		add_filter( 'gravityview_field_visibility_caps', array( $this, 'modify_visibility_caps'), 10, 5 );

		// Modify the field options based on the name of the field type
		add_filter( 'gravityview_template_delete_link_options', array( $this, 'delete_link_field_options' ), 10, 5 );

		// custom fields' options for zone EDIT
		add_filter( 'gravityview_template_field_options', array( $this, 'field_options' ), 10, 5 );

		// add template path to check for field
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

	}
		add_action( 'gravityview/edit-entry/publishing-action/after', array( $this, 'add_delete_button'), 10, 3 );

	function display_message() {
		// DISPLAY ERROR/SUCCESS MESSAGE
	}

	static function getInstance() {

		if( empty( self::$instance ) ) {
			self::$instance = new GravityView_Delete_Entry;
		}

		return self::$instance;
	}

	/**
	 * Include this extension templates path
	 * @param array $file_paths List of template paths ordered
	 */
	function add_template_path( $file_paths ) {

		// Index 100 is the default GravityView template path.
		// Index 110 is Edit Entry link
		$file_paths[ 115 ] = self::$file;

		return $file_paths;
	}

	/**
	 * Add "Delete Link Text" setting to the edit_link field settings
	 * @param  [type] $field_options [description]
	 * @param  [type] $template_id   [description]
	 * @param  [type] $field_id      [description]
	 * @param  [type] $context       [description]
	 * @param  [type] $input_type    [description]
	 * @return [type]                [description]
	 */
	function delete_link_field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		// Always a link, never a filter
		unset( $field_options['show_as_link'], $field_options['search_filter'] );

		// Edit Entry link should only appear to visitors capable of editing entries
		unset( $field_options['only_loggedin'], $field_options['only_loggedin_cap'] );

		$add_option['delete_link'] = array(
			'type' => 'text',
			'label' => __( 'Delete Link Text', 'gravityview' ),
			'desc' => NULL,
			'value' => __('Delete Entry', 'gravityview'),
			'merge_tags' => true,
		);

		return array_merge( $add_option, $field_options );
	}


	/**
	 * Manipulate the fields' options for the EDIT ENTRY screen
	 * @param  [type] $field_options [description]
	 * @param  [type] $template_id   [description]
	 * @param  [type] $field_id      [description]
	 * @param  [type] $context       [description]
	 * @param  [type] $input_type    [description]
	 * @return [type]                [description]
	 */
	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		//  Entry field is only for logged in users
		unset( $field_options['only_loggedin'], $field_options['only_loggedin_cap'] );

		$add_options = array(
			'allow_edit_cap' => array(
				'type' => 'select',
				'label' => __( 'Allow the following users to delete the entry:', 'gravityview' ),
				'choices' => GravityView_Render_Settings::get_cap_choices( $template_id, $field_id, $context, $input_type ),
				'tooltip' => 'allow_edit_cap',
				'class' => 'widefat',
				'value' => 'read', // Default: entry creator
			),
		);

		return array_merge( $field_options, $add_options );
	}



	/**
	 * Add Edit Link as a default field, outside those set in the Gravity Form form
	 * @param array $entry_default_fields Existing fields
	 * @param  string|array $form form_ID or form object
	 * @param  string $zone   Either 'single', 'directory', 'header', 'footer'
	 */
	function add_default_field( $entry_default_fields, $form = array(), $zone = '' ) {

		$entry_default_fields['delete_link'] = array(
			'label' => __('Delete Entry', 'gravityview'),
			'type' => 'delete_link',
			'desc'	=> __('A link to delete the entry. Respects the Edit Entry permissions.', 'gravityview'),
		);

		return $entry_default_fields;
	}

	/**
	 * Add Edit Entry Link to the Add Field dialog
	 * @param array $available_fields
	 */
	function add_available_field( $available_fields = array() ) {

		$available_fields['delete_link'] = array(
			'label_text' => __( 'Delete Entry', 'gravityview' ),
			'field_id' => 'delete_link',
			'label_type' => 'field',
			'input_type' => 'delete_link',
			'field_options' => NULL
		);

		return $available_fields;
	}

	/**
	 * Change wording for the Edit context to read Entry Creator
	 *
	 * @param  array 	   $visibility_caps        Array of capabilities to display in field dropdown.
	 * @param  string      $field_type  Type of field options to render (`field` or `widget`)
	 * @param  string      $template_id Table slug
	 * @param  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
	 * @param  string      $context     What context are we in? Example: `single` or `directory`
	 * @param  string      $input_type  (textarea, list, select, etc.)
	 * @return array                   Array of field options with `label`, `value`, `type`, `default` keys
	 */
	function modify_visibility_caps( $visibility_caps = array(), $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		$caps = $visibility_caps;

		// If we're configuring fields in the edit context, we want a limited selection
		if( $field_id === 'delete_link' ) {

			// Remove other built-in caps.
			unset( $caps['publish_posts'], $caps['gravityforms_view_entries'], $caps['delete_others_posts'] );

			$caps['read'] = _x('Entry Creator','User capability', 'gravityview');
		}

		return $caps;
	}

	/**
	 * Get the current entry and set it if it's not yet set.
	 * @return array Gravity Forms entry array
	 */
	private function get_entry() {

		if( empty( $this->entry ) ) {
			// Get the database value of the entry that's being edited
			$this->entry = gravityview_get_entry( GravityView_frontend::is_single_entry() );
		}

		return $this->entry;
	}

	function setup_vars( $entry = null ) {
		global $gravityview_view;

		$this->entry = empty( $entry ) ? $gravityview_view->entries[0] : $entry;
		$this->form = $gravityview_view->form;
		$this->form_id = $gravityview_view->form_id;
		$this->view_id = $gravityview_view->view_id;

		self::$nonce_key = sprintf( 'delete_%d', $this->entry['id'] );
	}


	/**
	 * Generate a nonce link with the base URL of the current View embed
	 *
	 * We don't want to link to the single entry, because when deleted, there would be nothing to return to.
	 *
	 * @filter default text
	 * @action default text
	 * @param  [type]      $entry [description]
	 * @param  [type]      $field [description]
	 * @return [type]             [description]
	 */
	static function get_delete_link( $entry, $field ) {

		self::getInstance()->setup_vars( $entry );

		$base = GravityView_API::directory_link( NULL, false );

		// Use the slug instead of the ID for consistent security
		$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );

		$actionurl = add_query_arg( array(
			'action'	=> 'delete',
			'entry_id'		=> $entry_slug
		), $base );

		$url = wp_nonce_url( $actionurl, 'delete_'.$entry_slug, 'delete' );

		return $url;
	}


	/**
	 * Add a Delete button to the #publishing-action section of the Delete Entry form
	 *
	 * @since 1.5.1
	 * @param array $form    Gravity Forms form array
	 * @param array $entry   Gravity Forms entry array
	 * @param int $view_id GravityView View ID
	 */
	function add_delete_button( $form = array(), $entry = array(), $view_id = NULL ) {
		global $gravityview_view;

		// Only show the link to those who are allowed to see it.
		if( !self::check_user_cap_delete_entry( $entry ) ) {
			return;
		}

		?>
		<a class="btn btn-sm button button-small alignright pull-right btn-danger" tabindex="5" href="<?php echo self::get_delete_link( $entry ); ?>"><?php esc_attr_e( 'Delete', 'gravityview' ); ?></a>
		<?php
	}
	function process_delete() {

		// If the form is submitted
		if( RGForms::get("action") === "delete") {

			$nonce_key = 'delete_'.$_GET['entry_id'];

			// Make sure it's a valid request
			check_admin_referer( $nonce_key, 'deleteasdasd' );

			// Get the entry slug
			$entry_slug = esc_attr( $_GET['entry_id'] );

			// See if there's an entry there
			$entry = gravityview_get_entry( $entry_slug );

			$error_message = 'asdsadsd';

			if( $entry ) {

				do_action('gravityview_log_debug', 'GravityView_Edit_Entry[process_delete] Starting delete entry: ', $entry );

				$delete_response = GFAPI::delete_entry( $entry['id'] );

				do_action('gravityview_log_debug', 'GravityView_Edit_Entry[process_delete] Delete response: ', $delete_response );

				// GFAPI::delete_entry() returns a WP_Error on error
				if( is_wp_error( $delete_response ) ) {

					$messages = $error_message;

				} else {

					$messages = array(
						'gv-delete-success' => 'asdsadsd'
					);

				}

			} else {

				do_action('gravityview_log_debug', 'GravityView_Edit_Entry[process_delete] Delete entry failed: there was no entry with the entry slug '.$entry_slug );

				$messages = $error_message;
			}

			$redirect_to_base = remove_query_arg( array('action', 'entry_id', 'delete', $nonce_key ) );


			$redirect_to = add_query_arg( $messages, $redirect_to_base );

			wp_safe_redirect( $redirect_to );

			exit();

		} // endif action is delete.

	} // process_delete

	/**
	 * Is the current page an Edit Entry page?
	 * @return boolean
	 */
	public function is_delete_entry() {

		$gf_page = ( 'entry' === RGForms::get("view") );

		return ( $gf_page && isset( $_GET['delete'] ) || RGForms::post("action") === "delete" );
	}

	/**
	 * Is the current nonce valid for editing the entry?
	 * @return boolean
	 */
	public function verify_nonce() {

		// Verify form submitted for editing single
		if( !empty( $_POST['is_gv_edit_entry'] ) ) {
			return wp_verify_nonce( $_POST['is_gv_edit_entry'], 'is_gv_edit_entry' );
		}

		// Verify
		if( ! $this->is_delete_entry() ) { return false; }

		return wp_verify_nonce( $_GET['delete'], self::$nonce_key );

	}

	/**
	 * Check if the user can edit the entry
	 *
	 * - Is the nonce valid?
	 * - Does the user have the right caps for the entry
	 * - Is the entry in the trash?
	 *
	 * @param  boolean $echo Show error messages in the form?
	 * @return boolean        True: can edit form. False: nope.
	 */
	function user_can_delete_entry( $echo = false ) {

		$error = NULL;

		if( ! $this->verify_nonce() ) {
			$error = __( 'The link to delete this entry is not valid; it may have expired.', 'gravityview');
		}

		if( ! self::check_user_cap_delete_entry( $this->entry ) ) {
			$error = __( 'You do not have permission to delete this entry.', 'gravityview');
		}

		if( $this->entry['status'] === 'trash' ) {
			$error = __('You cannot delete the entry; it is already in the trash.', 'gravityview' );
		}

		// No errors; everything's fine here!
		if( empty( $error ) ) {
			return true;
		}

		if( $echo ) {
			echo $this->generate_notice( wpautop( esc_html( $error ) ), 'gv-error error');
		}

		do_action('gravityview_log_error', 'GravityView_Edit_Entry[user_can_delete_entry]' . $error );

		return false;
	}

	/**
	 * checks if user has permissions to edit a specific entry
	 *
	 * Needs to be used combined with GravityView_Edit_Entry::user_can_edit_entry for maximum security!!
	 *
	 * @param  [type] $entry [description]
	 * @return bool
	 */
	public static function check_user_cap_delete_entry( $entry ) {
		global $gravityview_view;

		// Or if they can edit any entries (as defined in Gravity Forms), we're good.
		if( GFCommon::current_user_can_any( 'gravityforms_edit_entries' ) ) {
			return true;
		}

		if( !isset( $entry['created_by'] ) ) {

			do_action('gravityview_log_error', 'GravityView_Edit_Entry[check_user_cap_delete_entry] Entry `created_by` doesn\'t exist.');

			return false;
		}

		$user_edit = !empty( $gravityview_view->atts['user_edit'] );
		$current_user = wp_get_current_user();

		if( empty( $user_edit ) ) {

			do_action('gravityview_log_debug', 'GravityView_Edit_Entry[check_user_cap_delete_entry] User Edit is disabled. Returning false.' );

			return false;
		}

		// If the logged-in user is the same as the user who created the entry, we're good.
		if( is_user_logged_in() && intval( $current_user->ID ) === intval( $entry['created_by'] ) ) {

			do_action('gravityview_log_debug', sprintf( 'GravityView_Edit_Entry[check_user_cap_delete_entry] User %s created the entry.', $current_user->ID ) );

			return true;
		}

		return false;
	}


	function generate_notice( $notice, $class = '' ) {
		return '<div class="gv-notice '.esc_attr( $class ) .'">'. $notice .'</div>';
	}


} // end class

new GravityView_Delete_Entry;

