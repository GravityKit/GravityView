<?php
/**
 * GravityView Edit Entry - render frontend
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

use GravityKit\GravityView\Foundation\Helpers\WP as WPHelper;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class GravityView_Edit_Entry_Render {
	/**
	 * Value used to mark file upload as empty.
	 * @since 2.40
	 */
	private const EMPTY_FILE_UPLOAD_VALUE = '__GV_EMPTY_FILE__';

	/**
	 * Internal key used to track if a file input was disabled.
	 *
	 * @since 2.44
	 *
	 * @var string
	 */
	private const GV_FILE_UPLOAD_DISABLED = 'gv_field_disabled';

	/**
	 * @var GravityView_Edit_Entry
	 */
	protected $loader;

	/**
	 * @var string $nonce_key String used to generate unique nonce for the entry/form/view combination. Allows access to edit page.
	 */
	static $nonce_key;

	/**
	 * @since 1.9
	 * @var string $nonce_field String used for check valid edit entry form submission. Allows saving edit form values.
	 */
	private static $nonce_field = 'is_gv_edit_entry';

	/**
	 * @since 1.9
	 * @var bool Whether to allow save and continue functionality
	 */
	private static $supports_save_and_continue = false;

	/**
	 * Gravity Forms entry array
	 *
	 * @var array
	 */
	public $entry;

	/**
	 * The View.
	 *
	 * @var \GV\View.
	 * @since develop
	 */
	public $view;

	/**
	 * Gravity Forms entry array (it won't get changed during this class lifecycle)
     *
	 * @since 1.17.2
	 * @var array
	 */
	private static $original_entry = array();

	/**
	 * Gravity Forms form array (GravityView modifies the content through this class lifecycle)
	 *
	 * @var array
	 */
	public $form;

	/**
	 * Gravity Forms form array (it won't get changed during this class lifecycle)
     *
	 * @since 1.16.2.1
	 * @var array
	 */
	private static $original_form;

	/**
	 * Gravity Forms form array after the form validation process
     *
	 * @since 1.13
	 * @var array
	 */
	public $form_after_validation = null;

	/**
	 * Hold an array of GF field objects that have calculation rules
     *
	 * @var array
	 */
	public $fields_with_calculation = array();

	/**
	 * Gravity Forms form id
	 *
	 * @var int
	 */
	public $form_id;

	/**
	 * ID of the current view
	 *
	 * @var int
	 */
	public $view_id;

	/**
	 * ID of the current post. May also be ID of the current View.
     *
     * @since 2.0.13
	 *
     * @var int
	 */
	public $post_id;

	/**
	 * Updated entry is valid (GF Validation object)
	 *
	 * @var array
	 */
	public $is_valid = null;

	/**
	 * Internal page button states.
	 *
	 * @var bool
	 *
	 * @since develop
	 */
	public $show_previous_button;
	public $show_next_button;
	public $show_update_button;
	public $is_paged_submitted;
	private $unset_hidden_calculations = [];

	/**
	 * Holds files that should be removed.
	 *
	 * @since 2.40
	 *
	 * @var array<string, array<string, string>>
	 */
	private $remove_files = [];

	function __construct( GravityView_Edit_Entry $loader ) {
		$this->loader = $loader;
	}

	function load() {

		/** @define "GRAVITYVIEW_DIR" "../../../" */
		include_once GRAVITYVIEW_DIR . 'includes/class-admin-approve-entries.php';

		// Don't display an embedded form when editing an entry
		add_action( 'wp_head', array( $this, 'prevent_render_form' ) );
		add_action( 'wp_footer', array( $this, 'prevent_render_form' ) );

		// Stop Gravity Forms processing what is ours!
		add_action( 'wp', array( $this, 'prevent_maybe_process_form' ), 8 );
		add_action( 'admin_init', array( $this, 'prevent_maybe_process_form' ), 8 );

		add_filter( 'gravityview_is_edit_entry', array( $this, 'is_edit_entry' ) );

		add_action( 'gravityview_edit_entry', array( $this, 'init' ), 10, 4 );

		// Disable conditional logic if needed (since 1.9)
		add_filter( 'gform_has_conditional_logic', array( $this, 'manage_conditional_logic' ), 10, 2 );

		// Add fields expected by GFFormDisplay::validate()
		add_filter( 'gform_pre_validation', array( $this, 'gform_pre_validation' ) );

		add_filter( 'gform_multifile_upload_field',  [ $this, 'fake_existing_file_uploads' ], 10, 3 );
	}

	/**
	 * Don't show any forms embedded on a page when GravityView is in Edit Entry mode
	 *
	 * Adds a `__return_empty_string` filter on the Gravity Forms shortcode on the `wp_head` action
	 * And then removes it on the `wp_footer` action
	 *
	 * @since 1.16.1
	 *
	 * @return void
	 */
	public function prevent_render_form() {
		if ( $this->is_edit_entry() ) {
			if ( 'wp_head' === current_filter() ) {
				add_filter( 'gform_shortcode_form', '__return_empty_string' );
			} else {
				remove_filter( 'gform_shortcode_form', '__return_empty_string' );
			}
		}
	}

	/**
	 * Because we're mimicking being a front-end Gravity Forms form while using a Gravity Forms
	 * backend form, we need to prevent them from saving twice.
     *
	 * @return void
	 */
	public function prevent_maybe_process_form() {

	    if ( ! $this->is_edit_entry_submission() ) {
			return;
		}

		gravityview()->log->debug( 'GravityView_Edit_Entry[prevent_maybe_process_form] Removing GFForms::maybe_process_form() action.' );

		remove_action( 'wp', array( 'RGForms', 'maybe_process_form' ), 9 );
		remove_action( 'wp', array( 'GFForms', 'maybe_process_form' ), 9 );

		remove_action( 'admin_init', array( 'GFForms', 'maybe_process_form' ), 9 );
		remove_action( 'admin_init', array( 'RGForms', 'maybe_process_form' ), 9 );
	}

	/**
	 * Is the current page an Edit Entry page?
     *
	 * @return boolean
	 */
	public function is_edit_entry() {

		$is_edit_entry =
			( GravityView_frontend::is_single_entry() || gravityview()->request->is_entry() )
			&& ( ! empty( $_GET['edit'] ) );

		return ( $is_edit_entry || $this->is_edit_entry_submission() );
	}

	/**
	 * Is the current page an Edit Entry page?
     *
	 * @since 1.9
	 * @return boolean
	 */
	public function is_edit_entry_submission() {
		return ! empty( $_POST[ self::$nonce_field ] );
	}

	/**
	 * When Edit entry view is requested, set up key class variables
	 *
	 * @since 2.14.6 Added $view and $entry params
	 *
	 * @param \GV\View  $view  The View.
	 * @param \GV\Entry $entry The Entry.
	 *
	 * @return void
	 */
	private function setup_vars( $view, $entry ) {
		global $post;

		if ( $entry ) {
			self::$original_entry = $entry->as_entry();
			$entry                = $entry->as_entry();
		} else {
			$gravityview_view     = GravityView_View::getInstance();
			$entries              = $gravityview_view->getEntries();
			self::$original_entry = $entries[0];
			$entry                = $entries[0];
		}

		self::$original_form = GVCommon::get_form( $entry['form_id'] );

		$form = self::$original_form;

		/**
		 * Modifies form, entry, and View data before rendering the Edit Entry form.
		 *
		 * @filter `gk/gravityview/edit-entry/init/data`
		 *
		 * @since 2.48.4
		 *
		 * @param array $data {
		 *     @type array         $form  The form array.
		 *     @type array         $entry The entry array.
		 *     @type \GV\View|null $view  The View object.
		 * }
		 *
		 * @param GravityView_Edit_Entry_Render $this  The Edit Entry renderer instance.
		 */
		$data = apply_filters(
			'gk/gravityview/edit-entry/init/data',
			compact( 'form', 'entry', 'view' ),
			$this
		);

		$this->form  = $data['form'];
		$this->entry = $data['entry'];
		$this->view  = $data['view'];

		$this->form_id = $this->entry['form_id'];
		$this->view_id = $this->view ? $this->view->ID : $gravityview_view->getViewId();

		$this->post_id = \GV\Utils::get( $post, 'ID' );

		self::$nonce_key = GravityView_Edit_Entry::get_nonce_key( $this->view_id, $this->form_id, $this->entry['id'] );
	}

	/**
	 * Load required files and trigger edit flow
	 *
	 * Run when the is_edit_entry returns true.
	 *
	 * @param \GravityView_View_Data $gv_data GravityView Data object
	 * @param \GV\Entry              $entry   The Entry.
	 * @param \GV\View               $view    The View.
	 * @param \GV\Request            $request The Request.
	 *
	 * @since develop Added $entry, $view, $request adhocs.
	 *
	 * @return void
	 */
	public function init( $gv_data = null, $entry = null, $view = null, $request = null ) {

		require_once GFCommon::get_base_path() . '/form_display.php';
		require_once GFCommon::get_base_path() . '/entry_detail.php';

		$this->setup_vars( $view, $entry );

		if ( ! $gv_data ) {
			$gv_data = GravityView_View_Data::getInstance();
		}

		if ( $view && ! $gv_data->views->count() ) {
			$gv_data->views->add( $view );
		}

		// Multiple Views embedded, don't proceed if nonce fails
		if ( $gv_data->has_multiple_views() && ! $this->verify_nonce() ) {
			gravityview()->log->error( 'Nonce validation failed for the Edit Entry request; returning' );
			return;
		}

		// Sorry, you're not allowed here.
		if ( false === $this->user_can_edit_entry( true ) ) {
			gravityview()->log->error( 'User is not allowed to edit this entry; returning', array( 'data' => $this->entry ) );
			return;
		}

		$this->print_scripts();

		$this->process_save( $gv_data );

		$this->edit_entry_form();
	}


	/**
	 * Force Gravity Forms to output scripts as if it were in the admin
     *
	 * @return void
	 */
	private function print_scripts() {
		$gravityview_view = GravityView_View::getInstance();

		wp_register_script( 'gform_gravityforms', GFCommon::get_base_url() . '/js/gravityforms.js', array( 'jquery', 'gform_json', 'gform_placeholder', 'sack', 'plupload-all', 'gravityview-fe-view' ) );

		GFFormDisplay::enqueue_form_scripts( $this->form ? $this->form : $gravityview_view->getForm(), false );

		wp_localize_script( 'gravityview-fe-view', 'gvGlobals', array( 'cookiepath' => COOKIEPATH ) );

		wp_enqueue_script( 'sack' ); // Sack is required for images.
		wp_enqueue_script( 'gform_gravityforms' );
		wp_enqueue_script( 'gravityview-fe-view' );

		// File download/delete icons
		wp_enqueue_style( 'gform_admin_icons' );

		// Fixes the icons not showing up correctly in Gravity Forms 2.9+
		if ( version_compare( GFForms::$version, '2.9', '>=' ) ) {
			wp_add_inline_style( 'gform_theme', '.gform-icon { font-family: gform-icons-admin !important; }' );
		}

	}


	/**
	 * Process edit entry form save
	 *
	 * @param GravityView_View_Data $gv_data The View data.
	 */
	private function process_save( $gv_data ) {
		if ( empty( $_POST ) || ! isset( $_POST['lid'] ) ) {
			return;
		}

		// Yoast SEO can trigger a save action after one has been performed.
		if ( doing_action( 'wpseo_head' ) ) {
			return;
		}

		// Make sure the entry, view, and form IDs are all correct.
		$valid = $this->verify_nonce();

		if ( ! $valid ) {
			gravityview()->log->error( 'Nonce validation failed.' );
			return;
		}

		if ( $this->entry['id'] !== $_POST['lid'] ) {
			gravityview()->log->error( 'Entry ID did not match posted entry ID.' );
			return;
		}

		gravityview()->log->debug( '$_POSTed data (sanitized): ', array( 'data' => esc_html( print_r( $_POST, true ) ) ) );

		$this->process_save_process_files( $this->form_id );

		$this->validate();

		if ( $this->is_valid ) {

			gravityview()->log->debug( 'Submission is valid.' );

			/**
			 * @hack This step is needed to unset the adminOnly from form fields, to add the calculation fields
			 */
			$form = $this->form_prepare_for_save();

			$this->permanently_remove_deleted_files();

			/**
			 * @hack to avoid the capability validation of the method save_lead for GF 1.9+
			 */
			unset( $_GET['page'] );

			add_filter( 'gform_use_post_value_for_conditional_logic_save_entry', '__return_true' );

			/**
			 * Perform an action before the entry has been updated using Edit Entry.
			 *
			 * @since 2.1
			 * @param array $form Gravity Forms form array
			 * @param string $entry_id Numeric ID of the entry that is being updated
			 * @param GravityView_Edit_Entry_Render $this This object
			 * @param GravityView_View_Data $gv_data The View data
			 */
			do_action( 'gravityview/edit_entry/before_update', $form, $this->entry['id'], $this, $gv_data );

			GFFormsModel::save_lead( $form, $this->entry );

	        // Delete the values for hidden inputs
	        $this->unset_hidden_field_values();

			// Process calculation fields
			$this->update_calculation_fields();

			// Handle hidden approval fields (or their absense)
			$this->preset_approval_fields();

			// Perform actions normally performed after updating a lead
			$this->after_update();

	        /**
			 * Must be AFTER after_update()!
			 *
			 * @see https://github.com/gravityview/GravityView/issues/764
			 */
			$this->maybe_update_post_fields( $form );

			// Remove uploaded files from cache to clean the `gform_uploaded_files` field.
			$this->remove_uploaded_files( (int) $this->form_id );

			/**
			 * Perform an action after the entry has been updated using Edit Entry.
			 *
			 * @since 2.1 Added $gv_data parameter
			 * @param array $form Gravity Forms form array
			 * @param string $entry_id Numeric ID of the entry that was updated
			 * @param GravityView_Edit_Entry_Render $this This object
			 * @param GravityView_View_Data $gv_data The View data
			 */
			do_action( 'gravityview/edit_entry/after_update', $this->form, $this->entry['id'], $this, $gv_data );

			// Store the success message in transient and redirect to prevent resubmission.
			$this->store_message_and_redirect( 'success' );
		} else {
			$this->add_uploaded_file_sizes( (int) $this->form_id );
			$this->maybe_fix_valid_file_uploads();
			gravityview()->log->error( 'Submission is NOT valid.', array( 'entry' => $this->entry ) );
		}
	} // process_save

	/**
	 * Returns the transient key for the entry message.
	 *
	 * @since 2.44
	 *
	 * @return string The transient key.
	 */
	private function get_entry_msg_transient_key(): string {
		return 'gv_edit_entry_msg_' . md5( get_current_user_id() . '_' . $this->entry['id'] . '_' . $this->view_id );
	}

	/**
	 * Stores a message in a transient and redirect to prevent repeat form resubmission.
	 *
	 * This is called the Post-Redirect-Get (PRG) pattern.
	 *
	 * @since 2.44
	 *
	 * @param string $type Type of message: 'success' or 'error'.
	 */
	private function store_message_and_redirect( string $type ): void {
		if ( defined( 'DOING_GRAVITYVIEW_TESTS' ) && DOING_GRAVITYVIEW_TESTS ) {
			return;
		}
		// Generate a unique key for this user/entry combination.
		$transient_key = $this->get_entry_msg_transient_key();

		// Store the message type and related data in a transient for 60 seconds.
		WPHelper::set_transient(
			$transient_key,
			[
				'type'               => $type,
				'form_id'            => $this->form_id,
				'entry_id'           => $this->entry['id'],
				'view_id'            => $this->view_id,
				'is_valid'           => $this->is_valid,
				'is_paged_submitted' => $this->is_paged_submitted,
			],
			60
		);

		// Get the current URL without any form-related query args.
		$redirect_url = remove_query_arg( [ 'gform_submit', 'gform_unique_id', 'gform_source_page_number_' . $this->form_id, 'gform_field_values' ] );

		// Add a flag to indicate we've just saved.
		$redirect_url = add_query_arg( 'gv_updated', '1', $redirect_url );

		// Perform the redirect.
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Delete the value of fields hidden by conditional logic when the entry is edited
	 *
	 * @uses GFFormsModel::update_lead_field_value()
	 *
	 * @since 1.17.4
	 *
	 * @return void
	 */
	private function unset_hidden_field_values() {
	    global $wpdb;

		/**
		 * Whether to delete values of fields hidden by conditional logic.
		 *
		 * @since 1.22.2
		 * @param bool $unset_hidden_field_values Default: true
		 * @param GravityView_Edit_Entry_Render $this This object
		 */
		$unset_hidden_field_values = apply_filters( 'gravityview/edit_entry/unset_hidden_field_values', true, $this );

		$this->unset_hidden_calculations = array();

		if ( ! $unset_hidden_field_values ) {
			return;
		}

		if ( version_compare( GravityView_GFFormsModel::get_database_version(), '2.3-dev-1', '>=' ) && method_exists( 'GFFormsModel', 'get_entry_meta_table_name' ) ) {
			$entry_meta_table = GFFormsModel::get_entry_meta_table_name();
			$current_fields   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $entry_meta_table WHERE entry_id=%d", $this->entry['id'] ) );
		} else {
			$lead_detail_table = GFFormsModel::get_lead_details_table_name();
			$current_fields    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $lead_detail_table WHERE lead_id=%d", $this->entry['id'] ) );
		}

	    foreach ( $this->entry as $input_id => $field_value ) {

			if ( ! is_numeric( $input_id ) ) {
				continue;
			}

			if ( ! $field = RGFormsModel::get_field( $this->form, $input_id ) ) {
				continue;
			}

		    // Reset fields that are or would be hidden
		    if ( GFFormsModel::is_field_hidden( $this->form, $field, array(), $this->entry ) ) {

				$empty_value = $field->get_value_save_entry(
					is_array( $field->get_entry_inputs() ) ? array() : '',
					$this->form,
                    '',
                    $this->entry['id'],
                    $this->entry
				);

				if ( $field->has_calculation() ) {
					$this->unset_hidden_calculations[] = $field->id; // Unset
					$empty_value                       = '';
				}

				// We are intentionally marking this as empty, to force clear the value in `save_field_value()`.
				if ( $field->get_input_type() === 'fileupload' ) {
					$empty_value .= self::EMPTY_FILE_UPLOAD_VALUE;
				}

			    $lead_detail_id = GFFormsModel::get_lead_detail_id( $current_fields, $input_id );

			    GFFormsModel::update_lead_field_value( $this->form, $this->entry, $field, $lead_detail_id, $input_id, $empty_value );

			    // Prevent the $_POST values of hidden fields from being used as default values when rendering the form
				// after submission
			    $post_input_id           = 'input_' . str_replace( '.', '_', $input_id );
			    $_POST[ $post_input_id ] = '';
		    }
	    }
	}

	/**
	 * Leverage `gravityview/approve_entries/update_unapproved_meta` to prevent
	 * the missing/empty approval field to affect is_approved meta at all.
	 *
	 * Called before the Gravity Forms after_update triggers.
	 *
	 * @since 2.5
	 *
	 * @return void
	 */
	private function preset_approval_fields() {
		$has_approved_field = false;

		foreach ( self::$original_form['fields'] as $field ) {
			if ( $field->gravityview_approved ) {
				$has_approved_field = true;
				break;
			}
		}

		if ( ! $has_approved_field ) {
			return;
		}

		$is_field_hidden = true;

		foreach ( $this->form['fields'] as $field ) {
			if ( $field->gravityview_approved ) {
				$is_field_hidden = false;
				break;
			}
		}

		if ( ! $is_field_hidden ) {
			return;
		}

		add_filter( 'gravityview/approve_entries/update_unapproved_meta', array( $this, 'prevent_update_unapproved_meta' ), 9, 3 );
	}

	/**
	 * Done once from self::preset_approval_fields
	 *
	 * @since 2.5
	 *
	 * @return string UNAPPROVED unless something else is inside the entry.
	 */
	public function prevent_update_unapproved_meta( $value, $form, $entry ) {

		remove_filter( 'gravityview/approve_entries/update_unapproved_meta', array( $this, 'prevent_update_unapproved_meta' ), 9 );

		if ( ! $value = gform_get_meta( $entry['id'], 'is_approved' ) ) {

			$value = GravityView_Entry_Approval_Status::UNAPPROVED;

			$value = apply_filters( 'gravityview/approve_entries/after_submission/default_status', $value );
		}

		return $value;
	}

	/**
	 * Have GF handle file uploads
	 *
	 * Copy of code from GFFormDisplay::process_form()
	 *
	 * @param int $form_id
	 */
	private function process_save_process_files( $form_id ) {

		// Loading files that have been uploaded to temp folder
		$files = GFCommon::json_decode( stripslashes( RGForms::post( 'gform_uploaded_files' ) ) );
		if ( ! is_array( $files ) ) {
			$files = array();
		}

		/**
		 * Make sure the fileuploads are not overwritten if no such request was done.
		 *
		 * @since 1.20.1
		 */
		add_filter( "gform_save_field_value_$form_id", array( $this, 'save_field_value' ), 99, 5 );

		RGFormsModel::$uploaded_files[ $form_id ] = $files;
	}

	/**
	 * Make sure the fileuploads are not overwritten if no such request was done.
	 *
	 * TO ONLY BE USED INTERNALLY; DO NOT DEVELOP ON; MAY BE REMOVED AT ANY TIME.
	 *
	 * @since 1.20.1
	 *
	 * @param string              $value Field value
	 * @param array               $entry GF entry array
	 * @param GF_Field_FileUpload $field
	 * @param array               $form GF form array
	 * @param string              $input_id ID of the input being saved
	 *
	 * @return string
	 */
	public function save_field_value( $value = '', $entry = array(), $field = null, $form = array(), $input_id = '' ) {

		if ( ! $field || $field->get_input_type() !== 'fileupload' ) {
			return $value;
		}

		// We force this value to be empty in `unset_hidden_field_values()`.
		if ( strpos( $value, self::EMPTY_FILE_UPLOAD_VALUE ) !== false ) {
			return str_replace( self::EMPTY_FILE_UPLOAD_VALUE, '', $value );
		}

		$input_name = 'input_' . str_replace( '.', '_', $input_id );

		if ( $field->multipleFiles ) {
			$inner_entry = $this->get_entry();
			if ( self::EMPTY_FILE_UPLOAD_VALUE === \GV\Utils::get( $inner_entry, $input_id, '' ) ) {
				return '[]'; // Should be a JSON encoded empty array.
			}

			if ( empty( $value ) ) {
				return json_decode( \GV\Utils::get( $entry, $input_id, '' ), true );
			}

			return $value;
		}

		// Check if the field was disabled (no $_FILES or our custom marker is set).
		if (
			! isset( $_FILES[ $input_name ] )
			|| ! empty( $_FILES[ $input_name ][ self::GV_FILE_UPLOAD_DISABLED ] )
		) {
			// Field was disabled (file exists), keep the original value.
			return rgar( $entry, $input_id, $value );
		}

		// Field was enabled - check if a new file was uploaded.
		if ( empty( $_FILES[ $input_name ]['name'] ) ) {
			// Field was enabled but empty (file removed or never had one), clear the value.
			return '';
		}

		// New file was uploaded.
		return $value;
	}

	/**
	 * Set visibility to visible and convert field input key to string
     *
	 * @return array $form
	 */
	private function form_prepare_for_save() {

		$form = $this->filter_conditional_logic( $this->form );

	    /** @type GF_Field $field */
		foreach ( $form['fields'] as $k => &$field ) {

			/**
			 * Remove the fields with calculation formulas before save to avoid conflicts with GF logic
			 *
			 * @since 1.16.3
			 */
			if ( $field->has_calculation() ) {
				unset( $form['fields'][ $k ] );
			}

			$field->adminOnly = false;

			if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
				foreach ( $field->inputs as $key => $input ) {
				    $field->inputs[ $key ]['id'] = (string) $input['id'];
				}
			}
		}

		$form['fields'] = array_values( $form['fields'] );

		return $form;
	}

	private function update_calculation_fields() {
		global $wpdb;

		$form   = self::$original_form;
		$update = false;

		// get the most up to date entry values
		$entry = GFAPI::get_entry( $this->entry['id'] );

		if ( version_compare( GravityView_GFFormsModel::get_database_version(), '2.3-dev-1', '>=' ) && method_exists( 'GFFormsModel', 'get_entry_meta_table_name' ) ) {
			$entry_meta_table = GFFormsModel::get_entry_meta_table_name();
			$current_fields   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $entry_meta_table WHERE entry_id=%d", $entry['id'] ) );
		} else {
			$lead_detail_table = GFFormsModel::get_lead_details_table_name();
			$current_fields    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $lead_detail_table WHERE lead_id=%d", $entry['id'] ) );
		}

		if ( ! empty( $this->fields_with_calculation ) ) {
			$allowed_fields = $this->get_configured_edit_fields( $form, $this->view_id );
			$allowed_fields = wp_list_pluck( $allowed_fields, 'id' );

			foreach ( $this->fields_with_calculation as $field ) {

				if ( in_array( $field->id, $this->unset_hidden_calculations, true ) ) {
					continue;
				}

				$inputs = $field->get_entry_inputs();
				if ( is_array( $inputs ) ) {
				    foreach ( $inputs as $input ) {
						[ $field_id, $input_id ] = rgexplode( '.', $input['id'], 2 );

						if ( 'product' === $field->type ) {
							$input_name = 'input_' . str_replace( '.', '_', $input['id'] );

							// Only allow quantity to be set if it's allowed to be edited
							if ( in_array( $field_id, $allowed_fields ) && 3 == $input_id ) {
							} else { // otherwise set to what it previously was
								$_POST[ $input_name ] = $entry[ $input['id'] ];
							}
						} else {
							// Set to what it previously was if it's not editable
							if ( ! in_array( $field_id, $allowed_fields ) ) {
								$_POST[ $input_name ] = $entry[ $input['id'] ];
							}
						}

						GFFormsModel::save_input( $form, $field, $entry, $current_fields, $input['id'] );
				    }
				} else {
					// Set to what it previously was if it's not editable
					if ( ! in_array( $field->id, $allowed_fields ) ) {
						$_POST[ 'input_' . $field->id ] = $entry[ $field->id ];
					}
					GFFormsModel::save_input( $form, $field, $entry, $current_fields, $field->id );
				}
			}

			if ( method_exists( 'GFFormsModel', 'commit_batch_field_operations' ) ) {
				GFFormsModel::commit_batch_field_operations();
			}
		}
	}

	/**
	 * Handle updating the Post Image field
	 *
	 * Sets a new Featured Image if configured in Gravity Forms; otherwise uploads/updates media
	 *
	 * @since 1.17
	 *
	 * @uses GFFormsModel::media_handle_upload
	 * @uses set_post_thumbnail
	 *
	 * @param array    $form GF Form array
	 * @param GF_Field $field GF Field
	 * @param string   $field_id Numeric ID of the field
	 * @param string   $value
	 * @param array    $entry GF Entry currently being edited
	 * @param int      $post_id ID of the Post being edited
	 *
	 * @return mixed|string
	 */
	private function update_post_image( $form, $field, $field_id, $value, $entry, $post_id ) {

		$input_name = 'input_' . $field_id;

		if ( ! empty( $_FILES[ $input_name ]['name'] ) ) {

			// We have a new image

			$value = RGFormsModel::prepare_value( $form, $field, $value, $input_name, $entry['id'] );

			$ary     = ! empty( $value ) ? explode( '|:|', $value ) : array();
	        $ary     = stripslashes_deep( $ary );
			$img_url = \GV\Utils::get( $ary, 0 );

			$img_title       = count( $ary ) > 1 ? $ary[1] : '';
			$img_caption     = count( $ary ) > 2 ? $ary[2] : '';
			$img_description = count( $ary ) > 3 ? $ary[3] : '';

			$image_meta = array(
				'post_excerpt' => $img_caption,
				'post_content' => $img_description,
			);

			// adding title only if it is not empty. It will default to the file name if it is not in the array
			if ( ! empty( $img_title ) ) {
				$image_meta['post_title'] = $img_title;
			}

			/**
			 * todo: As soon as \GFFormsModel::media_handle_upload becomes a public method, move this call to \GFFormsModel::media_handle_upload and remove the hack from this class.
			 * Note: the method became public in GF 1.9.17.7, but we don't require that version yet.
			 */
			require_once GRAVITYVIEW_DIR . 'includes/class-gravityview-gfformsmodel.php';
			$media_id = GravityView_GFFormsModel::media_handle_upload( $img_url, $post_id, $image_meta );

			// is this field set as featured image?
			if ( $media_id && $field->postFeaturedImage ) {
				set_post_thumbnail( $post_id, $media_id );
			}
		} elseif ( ! empty( $_POST[ $input_name ] ) && is_array( $value ) ) {

			$img_url         = stripslashes_deep( $_POST[ $input_name ] );
			$img_title       = stripslashes_deep( \GV\Utils::_POST( $input_name . '_1' ) );
			$img_caption     = stripslashes_deep( \GV\Utils::_POST( $input_name . '_4' ) );
			$img_description = stripslashes_deep( \GV\Utils::_POST( $input_name . '_7' ) );

			$value = ! empty( $img_url ) ? $img_url . '|:|' . $img_title . '|:|' . $img_caption . '|:|' . $img_description : '';

			if ( $field->postFeaturedImage ) {

				$image_meta = array(
					'ID'           => get_post_thumbnail_id( $post_id ),
					'post_title'   => $img_title,
					'post_excerpt' => $img_caption,
					'post_content' => $img_description,
				);

				// update image title, caption or description
				wp_update_post( $image_meta );
			}
		} else {

			// if we get here, image was removed or not set.
			$value = '';

			if ( $field->postFeaturedImage ) {
				delete_post_thumbnail( $post_id );
			}
		}

		return $value;
	}

	/**
	 * Loop through the fields being edited and if they include Post fields, update the Entry's post object
	 *
	 * @param array $form Gravity Forms form
	 *
	 * @return void
	 */
	private function maybe_update_post_fields( $form ) {

		if ( empty( $this->entry['post_id'] ) ) {
	        gravityview()->log->debug( 'This entry has no post fields. Continuing...' );
			return;
		}

		$post_id = $this->entry['post_id'];

		// Security check
		if ( false === GVCommon::has_cap( 'edit_post', $post_id ) ) {
			gravityview()->log->error( 'The current user does not have the ability to edit Post #{post_id}', array( 'post_id' => $post_id ) );
			return;
		}

		$update_entry = false;

		$updated_post = $original_post = get_post( $post_id );

		foreach ( $this->entry as $field_id => $value ) {

			$field = RGFormsModel::get_field( $form, $field_id );

			if ( ! $field ) {
				continue;
			}

			if ( GFCommon::is_post_field( $field ) && 'post_category' !== $field->type ) {

				// Get the value of the field, including $_POSTed value
				$value = RGFormsModel::get_field_value( $field );

				// Use temporary entry variable, to make values available to fill_post_template() and update_post_image()
				$entry_tmp                  = $this->entry;
				$entry_tmp[ "{$field_id}" ] = $value;

				switch ( $field->type ) {

				    case 'post_title':
				        $post_title = $value;
				        if ( \GV\Utils::get( $form, 'postTitleTemplateEnabled' ) ) {
				            $post_title = $this->fill_post_template( $form['postTitleTemplate'], $form, $entry_tmp );
				        }
				        $updated_post->post_title = $post_title;
				        $updated_post->post_name  = $post_title;
				        unset( $post_title );
				        break;

				    case 'post_content':
				        $post_content = $value;
				        if ( \GV\Utils::get( $form, 'postContentTemplateEnabled' ) ) {
				            $post_content = $this->fill_post_template( $form['postContentTemplate'], $form, $entry_tmp, true );
				        }
				        $updated_post->post_content = $post_content;
				        unset( $post_content );
				        break;
				    case 'post_excerpt':
				        $updated_post->post_excerpt = $value;
				        break;
				    case 'post_tags':
				        wp_set_post_tags( $post_id, $value, false );
				        break;
				    case 'post_category':
				        break;
				    case 'post_custom_field':
						if ( $field->get_input_type() === 'fileupload' ) {
							break;
						}

						if ( is_array( $value ) && ( (float) $field_id !== (float) $field->id ) ) {
							$value = $value[ $field_id ];
						}

						if ( ! empty( $field->customFieldTemplateEnabled ) ) {
							$value = $this->fill_post_template( $field->customFieldTemplate, $form, $entry_tmp, true );
						}

						$value = $field->get_value_save_entry( $value, $form, '', $this->entry['id'], $this->entry );

				        update_post_meta( $post_id, $field->postCustomFieldName, $value );
				        break;

				    case 'post_image':
				        $value = $this->update_post_image( $form, $field, $field_id, $value, $this->entry, $post_id );
				        break;

				}

				// update entry after
				$this->entry[ "{$field_id}" ] = $value;

				$update_entry = true;

				unset( $entry_tmp );
			}
		}

		if ( $update_entry ) {

			$return_entry = GFAPI::update_entry( $this->entry );

			if ( is_wp_error( $return_entry ) ) {
				gravityview()->log->error(
                    'Updating the entry post fields failed',
                    array(
						'data' => array(
							'$this->entry'  => $this->entry,
							'$return_entry' => $return_entry,
						),
                    )
                );
			} else {
				gravityview()->log->debug( 'Updating the entry post fields for post #{post_id} succeeded', array( 'post_id' => $post_id ) );
			}
		}

		$return_post = wp_update_post( $updated_post, true );

		if ( is_wp_error( $return_post ) ) {
			$return_post->add_data( $updated_post, '$updated_post' );
			gravityview()->log->error( 'Updating the post content failed', array( 'data' => compact( 'updated_post', 'return_post' ) ) );
		} else {
			gravityview()->log->debug(
                'Updating the post content for post #{post_id} succeeded',
                array(
					'post_id' => $post_id,
					'data'    => $updated_post,
                )
            );
		}
	}

	/**
	 * Convert a field content template into prepared output
	 *
	 * @uses GravityView_GFFormsModel::get_post_field_images()
	 *
	 * @since 1.17
	 *
	 * @param string $template The content template for the field
	 * @param array  $form Gravity Forms form
	 * @param bool   $do_shortcode Whether to process shortcode inside content. In GF, only run on Custom Field and Post Content fields
	 *
	 * @return string
	 */
	private function fill_post_template( $template, $form, $entry, $do_shortcode = false ) {

		require_once GRAVITYVIEW_DIR . 'includes/class-gravityview-gfformsmodel.php';

		$post_images = GravityView_GFFormsModel::get_post_field_images( $form, $entry );

		// replacing post image variables
		$output = GFCommon::replace_variables_post_image( $template, $post_images, $entry );

		// replacing all other variables
		$output = GFCommon::replace_variables( $output, $form, $entry, false, false, false );

		// replace conditional shortcodes
		if ( $do_shortcode ) {
			$output = do_shortcode( $output );
		}

		return $output;
	}


	/**
	 * Perform actions normally performed after updating a lead
	 *
	 * @since 1.8
	 *
	 * @see GFEntryDetail::lead_detail_page()
	 *
	 * @return void
	 */
	private function after_update() {

		do_action( 'gform_after_update_entry', self::$original_form, $this->entry['id'], self::$original_entry );
		do_action( "gform_after_update_entry_{$this->form['id']}", self::$original_form, $this->entry['id'], self::$original_entry );

		// Re-define the entry now that we've updated it.
		$entry = RGFormsModel::get_lead( $this->entry['id'] );

		$entry = GFFormsModel::set_entry_meta( $entry, self::$original_form );

		if ( version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '<' ) ) {
			// We need to clear the cache because Gravity Forms caches the field values, which
			// we have just updated.
			foreach ( $this->form['fields'] as $key => $field ) {
				GFFormsModel::refresh_lead_field_value( $entry['id'], $field->id );
			}
		}

		/**
		 * Maybe process feeds.
		 *
		 * @since develop
		 */
		if ( $allowed_feeds = $this->view->settings->get( 'edit_feeds', array() ) ) {
			$feeds = GFAPI::get_feeds( null, $entry['form_id'] );
			if ( ! is_wp_error( $feeds ) ) {
				$registered_feeds = array();
				foreach ( GFAddOn::get_registered_addons() as $registered_feed ) {
					if ( is_subclass_of( $registered_feed, 'GFFeedAddOn' ) ) {
						if ( method_exists( $registered_feed, 'get_instance' ) ) {
							$registered_feed                                  = call_user_func( array( $registered_feed, 'get_instance' ) );
							$registered_feeds[ $registered_feed->get_slug() ] = $registered_feed;
						}
					}
				}
				foreach ( $feeds as $feed ) {
					if ( in_array( $feed['id'], $allowed_feeds ) ) {
						if ( $feed_object = \GV\Utils::get( $registered_feeds, $feed['addon_slug'] ) ) {
							$returned_entry = $feed_object->process_feed( $feed, $entry, self::$original_form );
							if ( is_array( $returned_entry ) && rgar( $returned_entry, 'id' ) ) {
								$entry = $returned_entry;
							}

							do_action( 'gform_post_process_feed', $feed, $entry, self::$original_form, $feed_object );
							$slug = $feed_object->get_slug();
							do_action( "gform_{$slug}_post_process_feed", $feed, $entry, self::$original_form, $feed_object );
						}
					}
				}
			}
		}

		$this->entry = $entry;
	}


	/**
	 * Display the Edit Entry form
	 *
	 * @return void
	 */
	public function edit_entry_form() {

		$view = \GV\View::by_id( $this->view_id );

		if ( $view->settings->get( 'edit_locking' ) ) {
			$locking = new GravityView_Edit_Entry_Locking();
			$locking->maybe_lock_object( $this->entry['id'] );
		}

		?>

		<div id="wpfooter"></div><!-- used for locking message -->

		<script>
			var ajaxurl = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>';
		</script>

		<div class="gv-edit-entry-wrapper">
        <?php

			$javascript = gravityview_ob_include( GravityView_Edit_Entry::$file . '/partials/inline-javascript.php', $this );

			/**
			 * Fixes weird wpautop() issue
			 *
			 * @see https://github.com/katzwebservices/GravityView/issues/451
			 */
			echo gravityview_strip_whitespace( $javascript );

		?>
			<h2 class="gv-edit-entry-title">
				<span>
                <?php

					/**
					 * Modify the edit entry title.
					 *
					 * @param string $edit_entry_title Modify the "Edit Entry" title
					 * @param GravityView_Edit_Entry_Render $this This object
					 */
					$edit_entry_title = apply_filters( 'gravityview_edit_entry_title', __( 'Edit Entry', 'gk-gravityview' ), $this );

					echo esc_attr( $edit_entry_title );
				?>
                    </span>
			</h2>

			<?php $this->maybe_print_message(); ?>

			<?php // The ID of the form needs to be `gform_{form_id}` for the pluploader ?>

			<form method="post" id="gform_<?php echo $this->form_id; ?>" enctype="multipart/form-data">

				<?php

				wp_nonce_field( self::$nonce_key, self::$nonce_key );

				wp_nonce_field( self::$nonce_field, self::$nonce_field, false );

				// Print the actual form HTML
				$this->render_edit_form();

				?>
			</form>

		</div>

		<?php
	}

	/**
	 * Display success or error message if the form has been submitted
	 *
	 * @uses GVCommon::generate_notice
	 *
	 * @since 1.16.2.2
	 *
	 * @return void
	 */
	private function maybe_print_message() {
		// Check if we have a message from a redirect (Post-Redirect-Get pattern).
		if ( '1' === ( \GV\Utils::_GET( 'gv_updated' ) ) ) {
			// Generate the transient key.
			$transient_key = $this->get_entry_msg_transient_key();

			// Get the stored message data.
			$message_data = WPHelper::get_transient( $transient_key );

			if ( $message_data ) {
				// Delete the transient so it's only shown once.
				WPHelper::delete_transient( $transient_key );

				// Set the properties from the stored data.
				$this->is_valid           = $message_data['is_valid'];
				$this->is_paged_submitted = $message_data['is_paged_submitted'];

				// Display the appropriate success message.
				$this->display_update_message();
				return;
			}
			// If we don't have a transient key, it's very likely that the form has been submitted again, so we continue.
		}

		if ( 'update' !== \GV\Utils::_POST( 'action' ) ) {
			return;
		}

		/**
		 * @filter `gravityview/features/paged-edit`
		 * @since 2.5
		 * @since 2.14 Added the $form argument
		 * @param bool $enable_paged_edit Should paged editing be supported?
		 * @param array $form The current form connected to the entry being edited
		 */
		$enable_paged_edit = apply_filters( 'gravityview/features/paged-edit', false, $this->form );

		if ( GFCommon::has_pages( $this->form ) && $enable_paged_edit ) {
			$labels = array(
				'cancel'   => __( 'Cancel', 'gk-gravityview' ),
				'submit'   => __( 'Update', 'gk-gravityview' ),
				'next'     => __( 'Next', 'gk-gravityview' ),
				'previous' => __( 'Previous', 'gk-gravityview' ),
			);

			/**
			 * Modify the cancel/submit buttons' labels.
			 *
			 * @since 1.16.3
			 *
			 * @param array $labels Default button labels associative array
			 * @param array $form The Gravity Forms form
			 * @param array $entry The Gravity Forms entry
			 * @param int $view_id The current View ID
			 */
			$labels = apply_filters( 'gravityview/edit_entry/button_labels', $labels, $this->form, $this->entry, $this->view_id );

			$this->is_paged_submitted = \GV\Utils::_POST( 'save' ) === $labels['submit'];
		}

		$this->display_update_message();
	}

	/**
	 * Display the update message based on validation status.
	 *
	 * @since 2.44
	 *
	 * @return void
	 */
	private function display_update_message() {
		$back_link = remove_query_arg( array( 'page', 'view', 'edit', 'gvid', 'gv_updated' ) );

		if ( ! $this->is_valid ) {
			// Keeping this compatible with Gravity Forms.
			$validation_message = "<div class='validation_error'>" . __( 'There was a problem with your submission.', 'gk-gravityview' ) . ' ' . __( 'Errors have been highlighted below.', 'gk-gravityview' ) . '</div>';
			$message            = apply_filters( "gform_validation_message_{$this->form['id']}", apply_filters( 'gform_validation_message', $validation_message, $this->form ), $this->form );

			echo GVCommon::generate_notice( $message, 'gv-error' );
			return;
		}

		if ( false === $this->is_paged_submitted ) {
			// Paged form that hasn't been submitted on the last page yet.
			$entry_updated_message = sprintf( esc_attr__( 'Entry Updated.', 'gk-gravityview' ), '<a href="' . esc_url( $back_link ) . '">', '</a>' );

			/**
			 * Modify the edit entry success message on pages.
			 *
			 * @since develop
			 * @param string $entry_updated_message Existing message
			 * @param int $view_id View ID
			 * @param array $entry Gravity Forms entry array
			 */
			$message = apply_filters( 'gravityview/edit_entry/page/success', $entry_updated_message, $this->view_id, $this->entry );

			echo GVCommon::generate_notice( $message );
			return;
		}

		$view              = \GV\View::by_id( $this->view_id );
		$edit_redirect     = $view->settings->get( 'edit_redirect' );
		$edit_redirect_url = $view->settings->get( 'edit_redirect_url' );

		switch ( $edit_redirect ) {
			case '0':
				$redirect_url          = $back_link;
				$entry_updated_message = sprintf( esc_attr_x( 'Entry Updated. %1$sReturning to Entry%2$s', 'Replacements are HTML', 'gk-gravityview' ), '<a href="' . esc_url( $redirect_url ) . '">', '</a>' );
				break;
			case '1':
				$redirect_url          = GravityView_API::directory_link();
				$entry_updated_message = sprintf( esc_attr_x( 'Entry Updated. %1$sReturning to %2$s%3$s', 'Replacement 1 is HTML. Replacement 2 is the title of the page where the user will be taken. Replacement 3 is HTML.', 'gk-gravityview' ), '<a href="' . esc_url( $redirect_url ) . '">', esc_html( $view->post_title ), '</a>' );
				break;
			case '2':
				$redirect_url          = $edit_redirect_url;
				$redirect_url          = GFCommon::replace_variables( $redirect_url, $this->form, $this->entry, false, false, false, 'text' );
				$entry_updated_message = sprintf( esc_attr_x( 'Entry Updated. %1$sRedirecting to %2$s%3$s', 'Replacement 1 is HTML. Replacement 2 is the URL where the user will be taken. Replacement 3 is HTML.', 'gk-gravityview' ), '<a href="' . esc_url( $redirect_url ) . '">', esc_html( $redirect_url ), '</a>' );
				break;
			case '':
			default:
				$entry_updated_message = sprintf( esc_attr__( 'Entry Updated. %1$sReturn to Entry%2$s', 'gk-gravityview' ), '<a href="' . esc_url( $back_link ) . '">', '</a>' );
				break;
		}

		if ( isset( $redirect_url ) ) {
			$entry_updated_message .= sprintf( '<script>window.location.href = %s;</script><noscript><meta http-equiv="refresh" content="0;URL=%s" /></noscript>', json_encode( $redirect_url ), esc_attr( $redirect_url ) );
		}

		/**
		 * Modify the edit entry success message (including the anchor link).
		 *
		 * @since  1.5.4
		 *
		 * @param string      $entry_updated_message Existing message
		 * @param int         $view_id               View ID
		 * @param array       $entry                 Gravity Forms entry array
		 * @param string      $back_link             URL to return to the original entry. @since 1.6
		 * @param string|null $redirect_url          URL to return to after the update. @since 2.14.6
		 */
		$message = apply_filters( 'gravityview/edit_entry/success', $entry_updated_message, $this->view_id, $this->entry, $back_link, isset( $redirect_url ) ? $redirect_url : null );

		echo GVCommon::generate_notice( $message );
	}

	/**
	 * Display the Edit Entry form in the original Gravity Forms format
	 *
	 * @since 1.9
	 *
	 * @return void
	 */
	private function render_edit_form() {

		/**
		 * Before rendering the Edit Entry form.
		 *
		 * @since 1.17
		 * @param GravityView_Edit_Entry_Render $this
		 */
		do_action( 'gravityview/edit-entry/render/before', $this );

		add_filter( 'gform_pre_render', [ $this, 'filter_modify_form_fields' ], 5000, 3 );
		add_filter( 'gform_has_conditional_logic', [ $this, 'set_default_values' ], 10, 2 );
		add_filter( 'gform_submit_button', [ $this, 'render_form_buttons' ] );
		add_filter( 'gform_next_button', [ $this, 'render_form_buttons' ] );
		add_filter( 'gform_previous_button', [ $this, 'render_form_buttons' ] );
		add_filter( 'gform_disable_view_counter', '__return_true' );

		add_filter( 'gform_field_input', [ $this, 'verify_user_can_edit_post' ], 5, 5 );
		add_filter( 'gform_field_input', [ $this, 'modify_edit_field_input' ], 10, 5 );

		// We need to remove the fake $_GET['page'] arg to avoid rendering form as if in admin.
		unset( $_GET['page'] );

		$this->show_next_button     = false;
		$this->show_previous_button = false;

		// TODO: Verify multiple-page forms
		if ( GFCommon::has_pages( $this->form ) && apply_filters( 'gravityview/features/paged-edit', false ) ) {
			if ( intval( $page_number = \GV\Utils::_POST( 'gform_source_page_number_' . $this->form['id'], 0 ) ) ) {

				$labels = array(
					'cancel'   => __( 'Cancel', 'gk-gravityview' ),
					'submit'   => __( 'Update', 'gk-gravityview' ),
					'next'     => __( 'Next', 'gk-gravityview' ),
					'previous' => __( 'Previous', 'gk-gravityview' ),
				);

				/**
				 * Modify the cancel/submit buttons' labels.
				 *
				 * @since 1.16.3
				 *
				 * @param array $labels Default button labels associative array
				 * @param array $form The Gravity Forms form
				 * @param array $entry The Gravity Forms entry
				 * @param int $view_id The current View ID
				 */
				$labels = apply_filters( 'gravityview/edit_entry/button_labels', $labels, $this->form, $this->entry, $this->view_id );

				GFFormDisplay::$submission[ $this->form['id'] ]['form']     = $this->form;
				GFFormDisplay::$submission[ $this->form['id'] ]['is_valid'] = true;

				if ( \GV\Utils::_POST( 'save' ) === $labels['next'] ) {
					$last_page = \GFFormDisplay::get_max_page_number( $this->form );

					while ( ++$page_number < $last_page && RGFormsModel::is_page_hidden( $this->form, $page_number, \GV\Utils::_POST( 'gform_field_values' ) ) ) {
					} // Advance to next visible page
				} elseif ( \GV\Utils::_POST( 'save' ) === $labels['previous'] ) {
					while ( --$page_number > 1 && RGFormsModel::is_page_hidden( $this->form, $page_number, \GV\Utils::_POST( 'gform_field_values' ) ) ) {
					} // Advance to next visible page
				}

				GFFormDisplay::$submission[ $this->form['id'] ]['page_number'] = $page_number;
			}

			if ( ( $page_number = intval( $page_number ) ) < 2 ) {
				$this->show_next_button = true; // First page
			}

			$last_page = \GFFormDisplay::get_max_page_number( $this->form );

			$has_more_pages = $page_number < $last_page;

			if ( $has_more_pages ) {
				$this->show_next_button = true; // Not the last page
			} else {
				$this->show_update_button = true; // The last page
			}

			if ( $page_number > 1 ) {
				$this->show_previous_button = true; // Not the first page
			}
		} else {
			$this->show_update_button = true;
		}

		ob_start(); // Prevent PHP warnings possibly caused by prefilling list fields for conditional logic

		$html = GFFormDisplay::get_form( $this->form['id'], false, false, true, $this->entry );

		ob_get_clean();

	    remove_filter( 'gform_pre_render', array( $this, 'filter_modify_form_fields' ), 5000 );
		remove_filter( 'gform_submit_button', array( $this, 'render_form_buttons' ) );
		remove_filter( 'gform_next_button', array( $this, 'render_form_buttons' ) );
		remove_filter( 'gform_previous_button', array( $this, 'render_form_buttons' ) );
		remove_filter( 'gform_disable_view_counter', '__return_true' );
		remove_filter( 'gform_field_input', array( $this, 'verify_user_can_edit_post' ), 5 );
		remove_filter( 'gform_field_input', array( $this, 'modify_edit_field_input' ), 10 );

		echo $html;

		/**
		 * After rendering the Edit Entry form.
		 *
		 * @since 1.17
		 * @param GravityView_Edit_Entry_Render $this
		 */
		do_action( 'gravityview/edit-entry/render/after', $this );
	}

	/**
	 * Display the Update/Cancel/Delete buttons for the Edit Entry form
     *
	 * @since 1.8
	 * @return string
	 */
	public function render_form_buttons() {
		return gravityview_ob_include( GravityView_Edit_Entry::$file . '/partials/form-buttons.php', $this );
	}


	/**
	 * Modify the form fields that are shown when using GFFormDisplay::get_form()
	 *
	 * By default, all fields will be shown. We only want the Edit Tab configured fields to be shown.
	 *
	 * @param array        $form
	 * @param boolean      $ajax Whether in AJAX mode
	 * @param array|string $field_values Passed parameters to the form
	 *
	 * @since 1.9
	 *
	 * @return array Modified form array
	 */
	public function filter_modify_form_fields( $form, $ajax = false, $field_values = '' ) {

		if ( $form['id'] != $this->form_id ) {
			return $form;
		}

		// In case we have validated the form, use it to inject the validation results into the form render
		if ( isset( $this->form_after_validation ) && $this->form_after_validation['id'] === $form['id'] ) {
			$form = $this->form_after_validation;
		} else {
			$form['fields'] = $this->get_configured_edit_fields( $form, $this->view_id );
		}

		$form = $this->filter_conditional_logic( $form );

		// for now we don't support Save and Continue feature.
		if ( ! self::$supports_save_and_continue ) {
	        unset( $form['save'] );
		}

		$form = $this->unselect_default_values( $form );

		return $form;
	}

	/**
	 * When displaying a field, check if it's a Post Field, and if so, make sure the post exists and current user has edit rights.
	 *
	 * @since 1.16.2.2
	 *
	 * @param string       $field_content Always empty. Returning not-empty overrides the input.
	 * @param GF_Field     $field
	 * @param string|array $value If array, it's a field with multiple inputs. If string, single input.
	 * @param int          $lead_id Lead ID. Always 0 for the `gform_field_input` filter.
	 * @param int          $form_id Form ID
	 *
	 * @return string If error, the error message. If no error, blank string (modify_edit_field_input() runs next)
	 */
	public function verify_user_can_edit_post( $field_content = '', $field = null, $value = '', $lead_id = 0, $form_id = 0 ) {

		if ( ! GFCommon::is_post_field( $field ) ) {
			return $field_content;
		}

        $message = null;

        // First, make sure they have the capability to edit the post.
		if ( null === get_post( $this->entry['post_id'] ) ) {
			/**
			 * Modify the message when someone is editing an entry attached to a post that no longer exists.
			 *
			 * @param string $message The existing "This field is not editable; the post no longer exists." text
			 */
			$message = apply_filters( 'gravityview/edit_entry/no_post_text', __( 'This field is not editable; the post no longer exists.', 'gk-gravityview' ) );
        } elseif ( false === current_user_can( 'edit_post', $this->entry['post_id'] ) ) {
			/**
			 * Modify the message when someone isn't able to edit a post.
			 *
			 * @param string $message The existing "You don't have permission..." text
			 */
			$message = apply_filters( 'gravityview/edit_entry/unsupported_post_field_text', __( 'You don&rsquo;t have permission to edit this post.', 'gk-gravityview' ) );
		}

        if ( $message ) {
            $field_content = sprintf( '<div class="ginput_container ginput_container_' . $field->type . '">%s</div>', wpautop( $message ) );
        }

        return $field_content;
	}

	/**
	 *
	 * Fill-in the saved values into the form inputs
	 *
	 * @param string       $field_content Always empty. Returning not-empty overrides the input.
	 * @param GF_Field     $field
	 * @param string|array $value If array, it's a field with multiple inputs. If string, single input.
	 * @param int          $lead_id Lead ID. Always 0 for the `gform_field_input` filter.
	 * @param int          $form_id Form ID
	 *
	 * @return mixed
	 */
	public function modify_edit_field_input( $field_content = '', $field = null, $value = '', $lead_id = 0, $form_id = 0 ) {

		$gv_field = GravityView_Fields::get_associated_field( $field );

		// If the form has been submitted, then we don't need to pre-fill the values,
		// Except for fileupload type and when a field input is overridden- run always!!

		if (
			( $this->is_edit_entry_submission() && ! in_array( $field->get_input_type(), array( 'fileupload', 'post_image' ) ) )
			&& false === ( $gv_field && is_callable( array( $gv_field, 'get_field_input' ) ) )
			&& ! GFCommon::is_product_field( $field->type )
			|| ! empty( $field_content )
			|| in_array( $field->type, array( 'honeypot' ) )
		) {
	        return $field_content;
		}

		// SET SOME FIELD DEFAULTS TO PREVENT ISSUES
		$field->adminOnly = false; /** @see GFFormDisplay::get_counter_init_script() need to prevent adminOnly */

		$field_value = $this->get_field_value( $field );

	    // Prevent any PHP warnings, like undefined index
	    ob_start();

	    $return = null;

		/** @var GravityView_Field $gv_field */
		if ( $gv_field && is_callable( array( $gv_field, 'get_field_input' ) ) ) {
			$return = $gv_field->get_field_input( $this->form, $field_value, $this->entry, $field );
		} else {
	        $return = $field->get_field_input( $this->form, $field_value, $this->entry );
	    }

	    // If there was output, it's an error
	    $warnings = ob_get_clean();

	    if ( ! empty( $warnings ) ) {
		    gravityview()->log->error(
                '{warning}',
                array(
					'warning' => $warnings,
					'data'    => $field_value,
                )
            );
	    }

		return $return;
	}

	/**
	 * Modify the value for the current field input
	 *
	 * @param GF_Field $field
	 *
	 * @return array|mixed|string
	 */
	private function get_field_value( $field ) {

		/**
		 * Allow the pre-populated value to override saved value in Edit Entry form. By default, pre-populate mechanism only kicks on empty fields.
		 *
		 * @param boolean True: override saved values; False: don't override (default)
		 * @param $field GF_Field object Gravity Forms field object
		 * @since 1.13
		 */
		$override_saved_value = apply_filters( 'gravityview/edit_entry/pre_populate/override', false, $field );

		// We're dealing with multiple inputs (e.g. checkbox) but not time or date (as it doesn't store data in input IDs)
		if ( isset( $field->inputs ) && is_array( $field->inputs ) && ! in_array( $field->type, array( 'time', 'date' ) ) && ! ( $field instanceof GF_Field_Radio && in_array($field->type, array('image_choice','multi_choice')) ) ) {

			$field_value = array();

			// only accept pre-populated values if the field doesn't have any choice selected.
			$allow_pre_populated = $field->allowsPrepopulate;

			foreach ( (array) $field->inputs as $input ) {

				$input_id = strval( $input['id'] );

				if ( isset( $this->entry[ $input_id ] ) && ! gv_empty( $this->entry[ $input_id ], false, false ) ) {
				    $field_value[ $input_id ] = 'post_category' === $field->type ? GFCommon::format_post_category( $this->entry[ $input_id ], true ) : $this->entry[ $input_id ];
				    $allow_pre_populated      = false;
				}
			}

			$pre_value = $field->get_value_submission( array(), false );

			// Fix for Address fields: ensure pre_value is an array when field expects it
			// Address fields may return an empty string in certain conditions but expect an array
			if ( 'address' === $field->type && ! is_array( $pre_value ) ) {
				$pre_value = [];
			}

			$field_value = ! $allow_pre_populated && ! ( $override_saved_value && ! gv_empty( $pre_value, false, false ) ) ? $field_value : $pre_value;

		} else {

			$id = intval( $field->id );

			// get pre-populated value if exists
			$pre_value = $field->allowsPrepopulate ? GFFormsModel::get_parameter_value( $field->inputName, array(), $field ) : '';

			// saved field entry value (if empty, fallback to the pre-populated value, if exists)
			// or pre-populated value if not empty and set to override saved value
			$field_value = isset( $this->entry[ $id ] ) && ! gv_empty( $this->entry[ $id ], false, false ) && ! ( $override_saved_value && ! gv_empty( $pre_value, false, false ) ) ? $this->entry[ $id ] : $pre_value;

			// in case field is post_category but inputType is select, multi-select or radio, convert value into array of category IDs.
			if ( 'post_category' === $field->type && ! gv_empty( $field_value, false, false ) ) {
				$categories = array();
				foreach ( explode( ',', $field_value ) as $cat_string ) {
				    $categories[] = GFCommon::format_post_category( $cat_string, true );
				}
				$field_value = 'multiselect' === $field->get_input_type() ? $categories : implode( '', $categories );
			}
		}

		// if value is empty get the default value if defined
		$field_value = $field->get_value_default_if_empty( $field_value );

	    /**
	     * Change the value of an Edit Entry field, if needed.
		 *
	     * @since 1.11
	     * @since 1.20 Added third param
	     * @param mixed $field_value field value used to populate the input
	     * @param object $field Gravity Forms field object ( Class GF_Field )
	     * @param GravityView_Edit_Entry_Render $this Current object
	     */
	    $field_value = apply_filters( 'gravityview/edit_entry/field_value', $field_value, $field, $this );

	    /**
	     * Change the value of an Edit Entry field for a specific field type.
		 *
	     * @since 1.17
	     * @since 1.20 Added third param
	     * @param mixed $field_value field value used to populate the input
	     * @param GF_Field $field Gravity Forms field object
	     * @param GravityView_Edit_Entry_Render $this Current object
	     */
	    $field_value = apply_filters( 'gravityview/edit_entry/field_value_' . $field->type, $field_value, $field, $this );

		return $field_value;
	}


	// ---- Entry validation

	/**
	 * Add field keys that Gravity Forms expects.
	 *
	 * @see GFFormDisplay::validate()
	 * @param  array $form GF Form
	 * @return array       Modified GF Form
	 */
	public function gform_pre_validation( $form ) {

		if ( ! $this->verify_nonce() ) {
			return $form;
		}

		// Fix PHP warning regarding undefined index.
		foreach ( $form['fields'] as &$field ) {

			// This is because we're doing admin form pretending to be front-end, so Gravity Forms
			// expects certain field array items to be set.
			foreach ( array( 'noDuplicates', 'adminOnly', 'inputType', 'isRequired', 'enablePrice', 'inputs', 'allowedExtensions' ) as $key ) {
	            $field->{$key} = isset( $field->{$key} ) ? $field->{$key} : null;
			}

			switch ( RGFormsModel::get_input_type( $field ) ) {

				/**
				 * this whole fileupload hack is because in the admin, Gravity Forms simply doesn't update any fileupload field if it's empty, but it DOES in the frontend.
				 *
				 * What we have to do is set the value so that it doesn't get overwritten as empty on save and appears immediately in the Edit Entry screen again.
				 *
				 * @hack
				 */
				case 'fileupload':
				    // Set the previous value
					$entry = $this->get_entry();

					$input_name = 'input_' . $field->id;
					$form_id    = $form['id'];

					// Use the previous entry value as the default.
					$value = $entry[ $field->id ] ?? '';

					// If this is a single upload file.
                    if ( ! \GV\Utils::get( $field, 'multipleFiles' ) ) {
                        if ( ! empty( $_FILES[ $input_name ] ) ) {
                            // Always remove the old file, if the <input type=file> is not disabled.
                            $this->record_files_for_removal( $field, $value );
                            // The new value to validate is now empty.
                            $value = '';
                            // Mark the old value as removed, to prevent Max Files Exceeded error.
                            $this->entry[ '' . $field->id ] = '';
                            if ( ! empty( $_FILES[ $input_name ][ 'name' ] ) ) {
                                // Uploading a new file.
                                $file_path = GFFormsModel::get_file_upload_path(
                                    $form_id,
                                    $_FILES[ $input_name ][ 'name' ]
                                );
                                $value     = $file_path[ 'url' ];
                            }
                        } else {
                            // Fix PHP warning on line 1498 of form_display.php for post_image fields
                            // Fix PHP Notice:  Undefined index:  size in form_display.php on line 1511.
                            $_FILES[ $input_name ] = [
                                'name'                        => '',
                                'size'                        => '',
                                self::GV_FILE_UPLOAD_DISABLED => true, // Custom marker to indicate field was disabled.
                            ];
                        }
                    } elseif ( \GV\Utils::get( $field, 'multipleFiles' ) ) {
                        // If there are fresh uploads, process and merge them.
                        // Otherwise, use the passed values, which should be json-encoded array of URLs.
                        if ( isset( GFFormsModel::$uploaded_files[ $form_id ][ $input_name ] ) ) {
                            $value = empty( $value ) ? '[]' : $value;
                        } elseif ( GFCommon::is_json( $value ) ) {
                            // Existing file; let GF derive the value from the `$_gf_uploaded_files` object (see `\GF_Field_FileUpload::get_multifile_value()`).
                            global $_gf_uploaded_files;

                            $_gf_uploaded_files[ $input_name ] = $value;
                        }

                        $is_cleared = false;
                        $this->record_files_for_removal( $field, (string) $value, $is_cleared );
                        if ( $is_cleared ) {
                            $value = self::EMPTY_FILE_UPLOAD_VALUE;
                        }

                        $this->entry[ '' . $field->id ] = $value;
                    }

					// Here for backwards compatibility, not sure if it can be removed.
					$this->entry[ $input_name ] = $value;

					break;

				case 'number':
				    // Fix "undefined index" issue at line 1286 in form_display.php
				    if ( ! isset( $_POST[ 'input_' . $field->id ] ) ) {
				        $_POST[ 'input_' . $field->id ] = null;
				    }
				    break;
			}
		}

		return $form;
	}


	/**
	 * Process validation for a edit entry submission
	 *
	 * Sets the `is_valid` object var
	 *
	 * @return void
	 */
	private function validate() {

		/**
		 * If using GF User Registration Add-on, remove the validation step, otherwise generates error when updating the entry
		 * GF User Registration Add-on version > 3.x has a different class name
		 *
		 * @since 1.16.2
		 */
		if ( class_exists( 'GF_User_Registration' ) ) {
			remove_filter( 'gform_validation', array( GF_User_Registration::get_instance(), 'validate' ) );
		} elseif ( class_exists( 'GFUser' ) ) {
			remove_filter( 'gform_validation', array( 'GFUser', 'user_registration_validation' ) );
		}

		/**
		 * For some crazy reason, Gravity Forms doesn't validate Edit Entry form submissions.
		 * You can enter whatever you want!
		 * We try validating, and customize the results using `self::custom_validation()`
		 */
		add_filter( 'gform_validation_' . $this->form_id, array( $this, 'custom_validation' ), 10, 4 );

		// Needed by the validation function.
		$failed_validation_page = null;
		$field_values           = rgpost( 'gform_field_values' );

		// Taken from GFForms::get_ajax_form_response()
		if ( is_string( $field_values ) ) {
			$field_values_array = [];
			parse_str( $field_values, $field_values_array );
			$field_values = $field_values_array;
		}

		// Prevent entry limit from running when editing an entry, also
		// prevent form scheduling from preventing editing
		unset( $this->form['limitEntries'], $this->form['scheduleForm'] );

		// Hide fields depending on Edit Entry settings
		$this->form['fields'] = $this->get_configured_edit_fields( $this->form, $this->view_id );

		$this->is_valid = GFFormDisplay::validate( $this->form, $field_values, 1, $failed_validation_page );

		remove_filter( 'gform_validation_' . $this->form_id, array( $this, 'custom_validation' ), 10 );
	}


	/**
	 * Make validation work for Edit Entry
	 *
	 * Because we're calling the GFFormDisplay::validate() in an unusual way (as a front-end
	 * form pretending to be a back-end form), validate() doesn't know we _can't_ edit post
	 * fields. This goes through all the fields and if they're an invalid post field, we
	 * set them as valid. If there are still issues, we'll return false.
	 *
	 * @param  $validation_results {
	 *   @type bool $is_valid
	 *   @type array $form
	 *   @type int $failed_validation_page The page number which has failed validation.
	 * }
	 *
	 * @return array
	 */
	public function custom_validation( $validation_results ) {

		gravityview()->log->debug( 'GravityView_Edit_Entry[custom_validation] Validation results: ', array( 'data' => $validation_results ) );

		gravityview()->log->debug( 'GravityView_Edit_Entry[custom_validation] $_POSTed data (sanitized): ', array( 'data' => esc_html( print_r( $_POST, true ) ) ) );

		$gv_valid = true;

		foreach ( $validation_results['form']['fields'] as $key => &$field ) {
			$posted_value      = GFFormsModel::get_field_value( $field );
			$field_type        = GFFormsModel::get_input_type( $field );
			$is_required       = ! empty( $field->isRequired );
			$failed_validation = ! empty( $field->failed_validation );

			// Use the posted value only if that value was posted. Fallback to original value.
			$value = isset( $_POST[ 'input_' . $field->id ] )
				? $posted_value
				: $this->entry[ $field->id ] ?? '';

			$value = apply_filters(
				'gk/gravityview/edit_entry/custom-validation-value',
				$value,
				$field,
				$this->entry ?? []
			);

			// Manually validate required fields as they can be skipped by GF's validation.
			// This can happen when the field is considered "hidden" (see `GFFormDisplay::validate`) due to unmet conditional logic.
			if (
				$is_required
				&& ! $failed_validation
				&& rgblank( $value )
				// The value might be empty for a single field upload, so also check `is_value_submission_empty`.
				&& $field->is_value_submission_empty( $validation_results['form']['id'] ?? 0 )
			) {
				if ( method_exists( $field, 'set_required_error' ) ) {
					$field->set_required_error( $value );
				} else {
					$field->failed_validation  = true;
					$field->validation_message = esc_html__( 'This field is required.', 'gk-gravityview' );
				}

				continue;
			}

			switch ( $field_type ) {
				case 'fileupload':
				case 'post_image':
					// If the value is an empty Array encoded as JSON it is still empty.
					if ( '[]' === $value ) {
						$value = '';
					}

					// Clear "this field is required" validation result when no files were uploaded but already exist on the server
					if ( $is_required && $failed_validation && ! empty( $value ) ) {
						$field->failed_validation = false;

						unset( $field->validation_message );
					}

					// Re-validate the field
					$field->validate( $value, $this->form );

					break;
			}

			// This field has failed validation.
			if ( ! empty( $field->failed_validation ) ) {

				gravityview()->log->debug(
                    'GravityView_Edit_Entry[custom_validation] Field is invalid.',
                    array(
						'data' => array(
							'field' => $field,
							'value' => $value,
						),
                    )
                );

				switch ( $field_type ) {

				    // Captchas don't need to be re-entered.
				    case 'captcha':
				        // Post Image fields aren't editable, so we un-fail them.
				    case 'post_image':
				        $field->failed_validation = false;
				        unset( $field->validation_message );
				        break;

				}

				// You can't continue inside a switch, so we do it after.
				if ( empty( $field->failed_validation ) ) {
				    continue;
				}

				// checks if the No Duplicates option is not validating entry against itself, since
				// we're editing a stored entry, it would also assume it's a duplicate.
				if ( ! empty( $field->noDuplicates ) ) {

				    $entry = $this->get_entry();

				    // If the value of the entry is the same as the stored value
				    // Then we can assume it's not a duplicate, it's the same.
				    if ( ! empty( $entry ) && $value == $entry[ $field->id ] ) {
				        // if value submitted was not changed, then don't validate
				        $field->failed_validation = false;

				        unset( $field->validation_message );

				        gravityview()->log->debug( 'GravityView_Edit_Entry[custom_validation] Field not a duplicate; it is the same entry.', array( 'data' => $entry ) );

				        continue;
				    }
				}

				// if here then probably we are facing the validation 'At least one field must be filled out'
				if ( GFFormDisplay::is_empty( $field, $this->form_id ) && empty( $field->isRequired ) ) {
				    unset( $field->validation_message );
					$field->failed_validation = false;
				    continue;
				}

				$gv_valid = false;

			}
		}

		$validation_results['is_valid'] = $gv_valid;

		gravityview()->log->debug( 'GravityView_Edit_Entry[custom_validation] Validation results.', array( 'data' => $validation_results ) );

		// We'll need this result when rendering the form ( on GFFormDisplay::get_form )
		$this->form_after_validation = $validation_results['form'];

		return $validation_results;
	}


	/**
	 * TODO: This seems to be hacky... we should remove it. Entry is set when updating the form using setup_vars()!
	 * Get the current entry and set it if it's not yet set.
     *
	 * @return array Gravity Forms entry array
	 */
	public function get_entry() {

		if ( empty( $this->entry ) ) {
			// Get the database value of the entry that's being edited
			$this->entry = gravityview_get_entry( GravityView_frontend::is_single_entry() );
		}

		return $this->entry;
	}



	// --- Filters

	/**
	 * Get the Edit Entry fields as configured in the View
	 *
	 * @since 1.8
	 *
	 * @param int $view_id
	 *
	 * @return array Array of fields that are configured in the Edit tab in the Admin
	 */
	private function get_configured_edit_fields( $form, $view_id ) {

		// Get all fields for form
		if ( \GV\View::exists( $view_id ) ) {
			$view       = \GV\View::by_id( $view_id );
			$properties = $view->fields ? $view->fields->as_configuration() : array();
		} else {
			$properties = null;
		}

		// If edit tab not yet configured, show all fields
		$edit_fields = ! empty( $properties['edit_edit-fields'] ) ? $properties['edit_edit-fields'] : null;

		// Hide fields depending on admin settings
		$fields = $this->filter_fields( $form['fields'], $edit_fields );

	    // If Edit Entry fields are configured, remove adminOnly field settings. Otherwise, don't.
	    $fields = $this->filter_admin_only_fields( $fields, $edit_fields, $form, $view_id );

		/**
		 * Modify the fields displayed in Edit Entry form.
		 *
		 * @since 1.17
		 * @param GF_Field[] $fields Gravity Forms form fields
		 * @param array|null $edit_fields Fields for the Edit Entry tab configured in the View Configuration
		 * @param array $form GF Form array (`fields` key modified to have only fields configured to show in Edit Entry)
		 * @param int $view_id View ID
		 */
		$fields = apply_filters( 'gravityview/edit_entry/form_fields', $fields, $edit_fields, $form, $view_id );

		return $fields;
	}


	/**
	 * Filter area fields based on specified conditions
	 *  - This filter removes the fields that have calculation configured
	 *  - Hides fields that are hidden, etc.
	 *
	 * @uses GravityView_Edit_Entry::user_can_edit_field() Check caps
	 * @access private
	 * @param GF_Field[] $fields
	 * @param array      $configured_fields
	 * @since  1.5
	 * @return array $fields
	 */
	private function filter_fields( $fields, $configured_fields ) {

		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return $fields;
		}

		$edit_fields = array();

		$field_type_blocklist = $this->loader->get_field_blocklist( $this->entry );

		if ( empty( $configured_fields ) && apply_filters( 'gravityview/features/paged-edit', false ) ) {
			$field_type_blocklist = array_diff( $field_type_blocklist, array( 'page' ) );
		}

		// First, remove blocklist or calculation fields
		foreach ( $fields as $key => $field ) {

			// Remove the fields that have calculation properties and keep them to be used later
			// @since 1.16.2
			if ( $field->has_calculation() ) {
				$this->fields_with_calculation[] = $field;
				// don't remove the calculation fields on form render.
			}

			if ( in_array( $field->type, $field_type_blocklist ) ) {
				unset( $fields[ $key ] );
			}
		}

		// The Edit tab has not been configured, so we return all fields by default.
		// But we do keep the hidden ones hidden please, for everyone :)
		if ( empty( $configured_fields ) ) {

			$out_fields = array();

			foreach ( $fields as &$field ) {

				/**
				 * @filter `gravityview/edit_entry/render_hidden_field`
				 * @see https://docs.gravitykit.com/article/678-edit-entry-hidden-fields-field-visibility
				 * @since 2.7
				 * @param bool $render_hidden_field Whether to render this Hidden field in HTML. Default: true
				 * @param GF_Field $field The field to possibly remove
				 */
				$render_hidden_field = apply_filters( 'gravityview/edit_entry/render_hidden_field', true, $field );

				if ( 'hidden' === $field->type && ! $render_hidden_field ) {
					continue; // Don't include hidden fields in the output
				}

				if ( 'hidden' == $field->visibility ) {
					continue; // Never include when no fields are configured
				}

				$out_fields[] = $field;
			}

			return array_values( $out_fields );
		}

		// The edit tab has been configured, so we loop through to configured settings
		foreach ( $configured_fields as $configured_field ) {

	        /** @var GF_Field $field */
	        foreach ( $fields as $field ) {
				if ( intval( $configured_field['id'] ) === intval( $field->id ) && $this->user_can_edit_field( $configured_field, false ) ) {
				    $edit_fields[] = static::merge_field_properties( $field, $configured_field );
				    break;
				}
			}
		}

		return $edit_fields;
	}

	/**
	 * Override GF Form field properties with the ones defined on the View
     *
	 * @param  GF_Field $field GF Form field object
	 * @param  array    $field_setting  GV field options
	 * @since  1.5
	 * @return array|GF_Field
	 */
	public static function merge_field_properties( $field, $field_setting ) {

		$return_field = $field;

		if ( empty( $field_setting['show_label'] ) ) {
			$return_field->label = '';
		} elseif ( ! empty( $field_setting['custom_label'] ) ) {
			$return_field->label = $field_setting['custom_label'];
		} elseif ( ! empty( $field_setting['label'] ) ) {
			$return_field->label = $field_setting['label'];
		}

		if ( ! empty( $field_setting['custom_class'] ) ) {
			$return_field->cssClass .= ' ' . gravityview_sanitize_html_class( $field_setting['custom_class'] );
		}

		/**
		 * Normalize page numbers - avoid conflicts with page validation
		 *
		 * @since 1.6
		 */
		$return_field->pageNumber = 1;

		return $return_field;
	}

	/**
	 * Remove fields that shouldn't be visible based on the Gravity Forms adminOnly field property
	 *
	 * @since 1.9.1
	 *
	 * @param array|GF_Field[] $fields Gravity Forms form fields
	 * @param array|null       $edit_fields Fields for the Edit Entry tab configured in the View Configuration
	 * @param array            $form GF Form array
	 * @param int              $view_id View ID
	 *
	 * @return array Possibly modified form array
	 */
	private function filter_admin_only_fields( $fields = array(), $edit_fields = null, $form = array(), $view_id = 0 ) {

		/**
		 * When Edit tab isn't configured, should the Gravity Forms "Admin Only" field settings be used to control field display to non-admins? Default: true.
		 * If the Edit Entry tab is not configured, adminOnly fields will not be shown to non-administrators.
		 * If the Edit Entry tab *is* configured, adminOnly fields will be shown to non-administrators, using the configured GV permissions
		 *
		 * @since 1.9.1
		 *
		 * @param boolean $use_gf_adminonly_setting True: Hide field if set to Admin Only in GF and the user is not an admin. False: show field based on GV permissions, ignoring GF permissions.
		 * @param array $form GF Form array
		 * @param int $view_id View ID
		 */
		$use_gf_adminonly_setting = apply_filters( 'gravityview/edit_entry/use_gf_admin_only_setting', empty( $edit_fields ), $form, $view_id );

	    if ( $use_gf_adminonly_setting && false === GVCommon::has_cap( 'gravityforms_edit_entries', $this->entry['id'] ) ) {
			foreach ( $fields as $k => $field ) {
				if ( $field->adminOnly ) {
				    unset( $fields[ $k ] );
				}
			}
			return array_values( $fields );
		}

	    foreach ( $fields as &$field ) {
		    $field->adminOnly = false;
		}

		return $fields;
	}

	/**
	 * Checkboxes and other checkbox-based controls should not
	 * display default checks in edit mode.
	 *
	 * https://github.com/gravityview/GravityView/1149
	 *
	 * @since 2.1
	 *
	 * @param array $form Gravity Forms array object
	 *
	 * @return array $form, modified to default checkboxes, radios from showing up.
	 */
	private function unselect_default_values( $form ) {

	    foreach ( $form['fields'] as &$field ) {

			if ( empty( $field->choices ) ) {
                continue;
			}

            foreach ( $field->choices as &$choice ) {
				if ( \GV\Utils::get( $choice, 'isSelected' ) ) {
					$choice['isSelected'] = false;
				}
			}
		}

		return $form;
	}

	// --- Conditional Logic

	/**
	 * Conditional logic isn't designed to work with forms that already have content. When switching input values,
	 * the dependent fields will be blank.
	 *
	 * We update the `defaultValue` of all the fields, just before the JavaScript is rendered, in order for the
	 * `defaults` key on the Conditional Logic configuration to be populated properly with the stored values.
	 *
	 * @since 2.48.2
	 *
	 * @param bool       $has_conditional_logic Whether the form has conditional logic
	 * @param array|null $form                  The forb object.
	 */
	public function set_default_values( bool $has_conditional_logic, ?array $form = null ): bool {
		if ( ! $has_conditional_logic || ! $form ) {
			return (bool) $has_conditional_logic;
		}

		// Have Conditional Logic pre-fill fields as if the data were default values
		/** @var GF_Field $field */
		foreach ( $form['fields'] as &$field ) {
			// The field has not yet rendered, so wait.
			if ( ! $field->get_context_property( 'rendering_form' ) ) {
				break;
			}

			if ( 'checkbox' === $field->type ) {
				foreach ( $field->get_entry_inputs() as $key => $input ) {
					$input_id = $input['id'];
					$choice   = $field->choices[ $key ];
					$value    = \GV\Utils::get( $this->entry, $input_id );
					$match    = RGFormsModel::choice_value_match( $field, $choice, $value );
					if ( $match ) {
						$field->choices[ $key ]['isSelected'] = true;
					}
				}
				continue;
			}

			if ( 'address' === $field->type ) {
				// Address fields have multiple inputs and need special handling.
				// This prevents defaultValue from being set to an empty string.
				$address_values = [];
				$inputs         = $field->get_entry_inputs();

				if ( is_array( $inputs ) ) {
					foreach ( $inputs as $input ) {
						$input_id = $input['id'];
						if ( isset( $this->entry[ $input_id ] ) ) {
							$address_values[ $input_id ] = $this->entry[ $input_id ];
						}
					}
				}

				// Only set defaultValue if we have address data, otherwise leave it unset.
				// to avoid string offset errors in Gravity Forms.
				if ( ! empty( $address_values ) ) {
					$field->defaultValue = $address_values;
				}

				continue;
			}

			$field_id = (float) $field->id;
			// We need to run through each field to set the default values.
			if ( ! isset( $this->entry[ $field_id ] ) ) {
				continue;
			}

			$field_value = $this->entry[ $field_id ];

			$field->defaultValue = $field_value;

			if ( 'list' === $field->type ) {
				$list_rows = maybe_unserialize( $field_value );

				$list_field_value = [];
				foreach ( (array) $list_rows as $row ) {
					foreach ( (array) $row as $column ) {
						$list_field_value[] = $column;
					}
				}

				$field->defaultValue = serialize( $list_field_value );
			}
		}

		return (bool) $has_conditional_logic;
	}

	/**
	 * Remove the conditional logic rules from the form button and the form fields, if needed.
	 *
	 * @todo Merge with caller method
	 * @since 1.9
	 *
	 * @param array $form Gravity Forms form
	 * @return array Modified form, if not using Conditional Logic
	 */
	private function filter_conditional_logic( $form ) {
		/**
		 * Fields that are tied to a conditional logic field that is not present in the view
		 * have to still be displayed, if the condition is met.
		 *
		 * @see https://github.com/gravityview/GravityView/issues/840
		 * @since develop
		 */
		$the_form     = GVCommon::get_form( $form['id'] );
		$editable_ids = array();
		foreach ( $form['fields'] as $field ) {
			$editable_ids[] = $field['id']; // wp_list_pluck is destructive in this context
		}
		$remove_conditions_rule = array();
		foreach ( $the_form['fields'] as $field ) {
			if ( ! empty( $field->conditionalLogic ) && ! empty( $field->conditionalLogic['rules'] ) ) {
				foreach ( $field->conditionalLogic['rules'] as $i => $rule ) {
					if ( ! in_array( $rule['fieldId'], $editable_ids ) ) {
						/**
						 * This conditional field is not editable in this View.
						 * We need to remove the rule, but only if it matches.
						 */
						if ( $_field = GFAPI::get_field( $the_form, $rule['fieldId'] ) ) {
							$value = $_field->get_value_export( $this->entry );
						} elseif ( isset( $this->entry[ $rule['fieldId'] ] ) ) {
							$value = $this->entry[ $rule['fieldId'] ];
						} else {
							$value = gform_get_meta( $this->entry['id'], $rule['fieldId'] );
						}

						$match = GVCommon::matches_operation( $value, $rule['value'], $rule['operator'] );

						if ( $match ) {
							$remove_conditions_rule[] = array( $field['id'], $i );
						}
					}
				}
			}
		}

		if ( $remove_conditions_rule ) {
			foreach ( $form['fields'] as &$field ) {
				foreach ( $remove_conditions_rule as $_remove_conditions_r ) {

				    [ $rule_field_id, $rule_i ] = $_remove_conditions_r;

					if ( $field['id'] == $rule_field_id ) {
						unset( $field->conditionalLogic['rules'][ $rule_i ] );
						gravityview()->log->debug(
                            'Removed conditional rule #{rule} for field {field_id}',
                            array(
								'rule'     => $rule_i,
								'field_id' => $field['id'],
                            )
                        );
					}
				}
			}
		}

		/** Normalize the indices... */
		$form['fields'] = array_values( $form['fields'] );

		/**
		 * Should the Edit Entry form use Gravity Forms conditional logic showing/hiding of fields?
		 *
		 * @since 1.9
		 * @param bool $use_conditional_logic True: Gravity Forms will show/hide fields just like in the original form; False: conditional logic will be disabled and fields will be shown based on configuration. Default: true
		 * @param array $form Gravity Forms form
		 */
		$use_conditional_logic = apply_filters( 'gravityview/edit_entry/conditional_logic', true, $form );

		if ( $use_conditional_logic ) {
			return $form;
		}

		foreach ( $form['fields'] as &$field ) {
			/* @var GF_Field $field */
			$field->conditionalLogic = null;
		}

		unset( $form['button']['conditionalLogic'] );

		return $form;
	}

	/**
	 * Disable the Gravity Forms conditional logic script and features on the Edit Entry screen
	 *
	 * @since 1.9
	 *
	 * @param $has_conditional_logic
	 * @param $form
	 * @return mixed
	 */
	public function manage_conditional_logic( $has_conditional_logic, $form ) {

		if ( ! $this->is_edit_entry() ) {
			return $has_conditional_logic;
		}

	    /** @see GravityView_Edit_Entry_Render::filter_conditional_logic for filter documentation */
		return apply_filters( 'gravityview/edit_entry/conditional_logic', $has_conditional_logic, $form );
	}


	// --- User checks and nonces

	/**
	 * Check if the user can edit the entry
	 *
	 * - Is the nonce valid?
	 * - Does the user have the right caps for the entry
	 * - Is the entry in the trash?
	 *
	 * @todo Move to GVCommon
	 *
	 * @param  boolean $echo Show error messages in the form?
	 * @return boolean        True: can edit form. False: nope.
	 */
	private function user_can_edit_entry( $echo = false ) {

		$error = null;

		/**
		 *  1. Permalinks are turned off
		 *  2. There are two entries embedded using oEmbed
		 *  3. One of the entries has just been saved
		 */
		if ( ! empty( $_POST['lid'] ) && ! empty( $_GET['entry'] ) && ( $_POST['lid'] !== $_GET['entry'] ) ) {

			$error = true;

		}

		if ( ! empty( $_GET['entry'] ) && (string) $this->entry['id'] !== $_GET['entry'] ) {

			$error = true;

		} elseif ( ! $this->verify_nonce() ) {

			/**
			 * If the Entry is embedded, there may be two entries on the same page.
			 * If that's the case, and one is being edited, the other should fail gracefully and not display an error.
			 */
			if ( GravityView_oEmbed::getInstance()->get_entry_id() ) {
				$error = true;
			} else {
				$error = __( 'The link to edit this entry is not valid; it may have expired.', 'gk-gravityview' );
			}
		}

		if ( ! GravityView_Edit_Entry::check_user_cap_edit_entry( $this->entry, $this->view ) ) {
			$error = __( 'You do not have permission to edit this entry.', 'gk-gravityview' );
		}

		if ( 'trash' === $this->entry['status'] ) {
			$error = __( 'You cannot edit the entry; it is in the trash.', 'gk-gravityview' );
		}

		// No errors; everything's fine here!
		if ( empty( $error ) ) {
			return true;
		}

		if ( $echo && true !== $error ) {

			$error = esc_html( $error );

			/**
			 * @since 1.9
			 */
			if ( ! empty( $this->entry ) ) {
				$error .= ' ' . gravityview_get_link( '#', _x( 'Go back.', 'Link shown when invalid Edit Entry link is clicked', 'gk-gravityview' ), array( 'onclick' => 'window.history.go(-1); return false;' ) );
			}

			echo GVCommon::generate_notice( wpautop( $error ), 'gv-error error' );
		}

		gravityview()->log->error( '{error}', array( 'error' => $error ) );

		return false;
	}


	/**
	 * Check whether a field is editable by the current user, and optionally display an error message
     *
	 * @uses  GravityView_Edit_Entry->check_user_cap_edit_field() Check user capabilities
	 * @param  array   $field Field or field settings array
	 * @param  boolean $echo  Whether to show error message telling user they aren't allowed
	 * @return boolean         True: user can edit the current field; False: nope, they can't.
	 */
	private function user_can_edit_field( $field, $echo = false ) {

		$error = null;

		if ( ! $this->check_user_cap_edit_field( $field ) ) {
			$error = __( 'You do not have permission to edit this field.', 'gk-gravityview' );
		}

		// No errors; everything's fine here!
		if ( empty( $error ) ) {
			return true;
		}

		if ( $echo ) {
			echo GVCommon::generate_notice( wpautop( esc_html( $error ) ), 'gv-error error' );
		}

		gravityview()->log->error( '{error}', array( 'error' => $error ) );

		return false;
	}


	/**
	 * checks if user has permissions to edit a specific field
	 *
	 * Needs to be used combined with GravityView_Edit_Entry::user_can_edit_field for maximum security!!
	 *
	 * @param  [type] $field [description]
	 * @return bool
	 */
	private function check_user_cap_edit_field( $field ) {

		// If they can edit any entries (as defined in Gravity Forms), we're good.
		if ( GVCommon::has_cap( array( 'gravityforms_edit_entries', 'gravityview_edit_others_entries' ) ) ) {
			return true;
		}

		$field_cap = isset( $field['allow_edit_cap'] ) ? $field['allow_edit_cap'] : false;

		if ( $field_cap ) {
			$has_cap = GVCommon::has_cap( $field['allow_edit_cap'] );

			/**
			 * @filter `gk/gravityview/edit-entry/user-can-edit-field` Filter whether the user can edit a specific field.
			 *
			 * @since 2.38.0
			 *
			 * @param bool     $has_cap True if the user has the capability to edit the field, false otherwise.
			 * @param array    $field The field settings array.
			 * @param array    $entry The Gravity Forms entry.
			 * @param \GV\View $view The GravityView view.
			 */
			return apply_filters( 'gk/gravityview/edit-entry/user-can-edit-field', $has_cap, $field, $this->entry, $this->view );
		}

		return false;
	}


	/**
	 * Is the current nonce valid for editing the entry?
     *
	 * @return boolean
	 */
	public function verify_nonce() {

		// Verify form submitted for editing single
		if ( $this->is_edit_entry_submission() ) {
			$valid = wp_verify_nonce( $_POST[ self::$nonce_field ], self::$nonce_field );
		}

		// Verify
		elseif ( ! $this->is_edit_entry() ) {
			$valid = false;
		} else {
			$valid = wp_verify_nonce( $_GET['edit'], self::$nonce_key );
		}

		/**
		 * Override Edit Entry nonce validation. Return true to declare nonce valid.
		 *
		 * @since 1.13
		 * @param int|boolean $valid False if invalid; 1 or 2 when nonce was generated
		 * @param string $nonce_field Key used when validating submissions. Default: is_gv_edit_entry
		 */
		$valid = apply_filters( 'gravityview/edit_entry/verify_nonce', $valid, self::$nonce_field );

		return $valid;
	}

	/**
	 * Returns labels for the action links on Edit Entry
	 *
	 * @since 2.10.4
	 *
	 * @return array `cancel`, `submit`, `next`, `previous` array keys with associated labels.
	 */
	public function get_action_labels() {

		$labels = array(
			'cancel'   => $this->view->settings->get( 'action_label_cancel', _x( 'Cancel', 'Shown when the user decides not to edit an entry', 'gk-gravityview' ) ),
			'submit'   => $this->view->settings->get( 'action_label_update', _x( 'Update', 'Button to update an entry the user is editing', 'gk-gravityview' ) ),
			'next'     => $this->view->settings->get( 'action_label_next', __( 'Next', 'Show the next page in a multi-page form', 'gk-gravityview' ) ),
			'previous' => $this->view->settings->get( 'action_label_previous', __( 'Previous', 'Show the previous page in a multi-page form', 'gk-gravityview' ) ),
		);

		/**
		 * Modify the cancel/submit buttons' labels.
		 *
		 * @since 1.16.3
		 * @param array $labels Default button labels associative array
		 * @param array $form The Gravity Forms form
		 * @param array $entry The Gravity Forms entry
		 * @param int $view_id The current View ID
		 */
		$labels = apply_filters( 'gravityview/edit_entry/button_labels', $labels, $this->form, $this->entry, $this->view_id );

		return (array) $labels;
	}

	/**
	 * Removes any uploaded files from the internal cache after the form was valid and the entry was stored.
	 *
	 * @since 2.40
	 *
	 * @param int $form_id The form ID.
	 */
	private function remove_uploaded_files( int $form_id ): void {
		$tmp_path = $this->get_tmp_upload_path( $form_id );

		foreach ( GFFormsModel::$uploaded_files[ $form_id ] as $input_name => $files ) {
			if ( ! is_array( $files ) ) {
				continue;
			}

			foreach ( $files as $key => $file ) {
				// Skip files that don't have a temp_filename - these are likely already processed files
				// from a previous submission that are being referenced again.
				if ( empty( $file['temp_filename'] ) ) {
					continue;
				}

				$tmp_file = $tmp_path . wp_basename( $file['temp_filename'] );
				if ( ! file_exists( $tmp_file ) ) {
					unset( GFFormsModel::$uploaded_files[ $form_id ][ $input_name ][ $key ] );
				}
			}
		}
	}

	/**
	 * Add file size to add back to the uploader.
	 *
	 * @see fe-views.js `fix_updating_files_after_edit`.
	 *
	 * @since 2.40
	 *
	 * @param int $form_id The form ID.
	 */
	private function add_uploaded_file_sizes( int $form_id ): void {
		$tmp_path = $this->get_tmp_upload_path( $form_id );

		foreach ( GFFormsModel::$uploaded_files[ $form_id ] ?? [] as $input_name => $files ) {
			if ( ! is_array( $files ) ) {
				continue;
			}

			foreach ( $files as $key => $file ) {
				$tmp_file = $tmp_path . wp_basename( $file['temp_filename'] );
				if ( is_readable( $tmp_file ) ) {
					GFFormsModel::$uploaded_files[ $form_id ][ $input_name ][ $key ]['size'] = filesize( $tmp_file );
				}
			}
		}
	}

	/**
	 * Returns an upload result for existing temporary uploaded files.
	 *
	 * @since 2.40
	 *
	 * @param array $field The field object.
	 * @param array $form  The Form object.
	 */
	public function fake_existing_file_uploads( $field, $form ) {
		$file = rgpost( 'file', [] );
		// We inject this additional parameter on the file object for recognition.
		if ( ! rgar( $file, 'gv_is_existing', false ) ) {
			return $field;
		}

		$tmp_path = $this->get_tmp_upload_path( (int) ( $form['id'] ?? 0 ) );

		$tmp_file = $tmp_path . wp_basename( $file['temp_filename'] );
		if ( ! file_exists( $tmp_file ) ) {
			return null;
		}

		$output = [
			'status' => 'ok',
			'data'   => [
				'temp_filename'     => $file['temp_filename'],
				'uploaded_filename' => str_replace( "\\'", "'", urldecode( $file['uploaded_filename'] ) ),
			],
		];

		wp_send_json( $output, 200 );
	}

	/**
	 * Removes any files that have been deleted before validation.
	 *
	 * @since 2.40
	 */
	private function permanently_remove_deleted_files(): void {
		$entry_id = $this->entry['id'] ?? 0;

		if ( ! $entry_id ) {
			return;
		}

		foreach ( $this->remove_files as $field_id => $files ) {
			// Reversing the order because files are removed one by one. This would change the index after every removal.
			foreach ( array_reverse( $files, true ) as $file_index => $file_name ) {
				GFFormsModel::delete_file( $entry_id, $field_id, $file_index );
			}
		}
	}

	/**
	 * Returns the temporary upload path for a form.
	 *
	 * @since 2.40
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return string The path.
	 */
	private function get_tmp_upload_path( int $form_id ): string {
		if ( method_exists( GFFormsModel::class, 'get_tmp_upload_location' ) ) {
			$tmp_location = GFFormsModel::get_tmp_upload_location( $form_id );

			$path = $tmp_location['path'] ?? '';
			if ( $path ) {
				return $path;
			}
		}

		return GFFormsModel::get_upload_path( $form_id ) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Records any files to remove after validation.
	 *
	 * @since 2.40
	 *
	 * @param GF_Field $field      The FileUpload field.
	 * @param string   $value      The current entry value for the field.
	 * @param bool     $is_cleared Reference to update when all files are cleared.
	 */
	private function record_files_for_removal( GF_Field $field, string $value, bool &$is_cleared = false ): void {
		/**
		 * @filter `gk/gravityview/edit-entry/record-file-removal` Modifies whether to record files for removal.
		 *
		 * @since  2.40
		 *
		 * @param bool                $should_record Whether to record.
		 * @param GF_Field_FileUpload $field         The FileUpload field.
		 * @param string              $value         The current entry value for the field.
		 */
		$should_record = (bool) apply_filters(
			'gk/gravityview/edit-entry/record-file-removal',
			true,
			$field,
			$value
		);

		$input_name = 'input_' . $field->id;
		$form_id    = (int) $field->formId;

		if (
			! $should_record
			|| ! ( isset( $_POST[ $input_name ] ) || isset( $_FILES[ $input_name ] ) )
		) {
			return;
		}

		if ( ! ( $field->multipleFiles ?? false ) && ! empty( $value ) ) {
			$this->remove_files[ '' . $field->id ] = [ $value ];

			return;
		}

		if ( ! GFCommon::is_json( $value ) ) {
			return;
		}

		$posted_value = GFFormsModel::get_field_value( $field );
		// This contains the files that were on the entry before the form was submitted.
		$files_in_entry = json_decode( $value, true ) ?: [];
		// This contains the files that are supposed to stay on the entry after the form is submitted.
		// NOTE: This does not include any files that are going to be uploaded!
		$files_to_keep = json_decode( $posted_value, true ) ?: [];
		// This contains the existing files that are going to be kept. This seems superfluous, but we require this
		// array to calculate the correct index of the file to remove.
		$kept_files = array_intersect( $files_in_entry, $files_to_keep );

		// If new files are being uploaded, the entry should not be cleared.
		$has_new_uploads = ! empty( GFFormsModel::$uploaded_files[ $form_id ][ $input_name ] ?? [] );

		if ( ! $has_new_uploads && $files_in_entry && ! $files_to_keep ) {
			$is_cleared = true;
		}

		// We use this strategy to keep the indexes of the original array, as we need them for removal.
		$this->remove_files[ '' . $field->id ] = array_diff( $files_in_entry, $kept_files );
	}

	/**
	 * Reverts the old values for single file uploads as the files are not uploaded to a temporary folder.
	 *
	 * When the validation of the form fails, the upload information is gone for subsequent posts. To avoid confusion,
	 * we reset the old value; and mark the field with an "error".
	 *
	 * @since 2.40
	 *
	 * @param GF_Field $field The upload field.
	 */
	private function maybe_revert_single_file_upload( GF_Field $field ): void {
		$original_entry = GFAPI::get_entry( $this->entry['id'] ?? 0 );
		if ( ! $original_entry ) {
			return;
		}

		$old_value = $original_entry[ $field->id ] ?? null;
		$value     = $this->entry[ $field->id ] ?? null;

		if ( $old_value === $value ) {
			// Nothing changed, so no extra problems.
			return;
		}

		// Return old value.
		$this->entry[ '' . $field->id ] = $old_value;

		// If a new value was set, we need to make sure the user provides the file again.
		if (
			! empty( $_FILES[ 'input_' . $field->id ]['name'] )
			&& ! $field->failed_validation // Other validation takes priority.
		) {
			$field->failed_validation  = true;
			$field->validation_message = strtr(
			// Translators: [b] and [/b] are replaced by `<strong>` and `</strong>` tags.
				esc_html__(
					'[b]Note:[/b] For security reasons you must upload the file again.',
					'gk-gravityview'
				),
				[
					'[b]'  => '<strong>',
					'[/b]' => '</strong>',
				]
			);
		}
	}

	/**
	 * Unsets values marked for removal on a valid multiple files upload field.
	 *
	 * If the form does not validate, but the upload field IS valid, we want to keep any removed files from showing up
	 * in the form again. If the field does not validate, we keep those old values, to make the UI more intuitive.
	 *
	 * @since 2.40
	 *
	 * @param GF_Field $field The upload field.
	 */
	private function maybe_unset_multiple_file_upload( GF_Field $field ): void {
		if (
			! isset( $this->remove_files[ $field->id ] )
			|| $field->failed_validation
		) {
			return;
		}

		$value         = $this->entry[ $field->id ] ?? '[]';
		$current_files = GFCommon::is_json( $value ) ? json_decode( $value, true ) : [];
		if ( empty ( $current_files ) ) {
			// This shouldn't be possible, just a sanity check.
			return;
		}

		$files_to_keep = array_values( array_diff( $current_files, $this->remove_files[ $field->id ] ) );

		try {
			$this->entry[ '' . $field->id ] = json_encode( $files_to_keep, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			// Fall through by design.
		}
	}

	/**
	 * Handles the field value for field uploads on invalid forms.
	 *
	 * @since 2.40
	 */
	private function maybe_fix_valid_file_uploads(): void {
		foreach ( $this->form['fields'] ?? [] as $field ) {
			if ( 'fileupload' !== $field->get_input_type() ) {
				continue;
			}

			$field->multipleFiles
				? $this->maybe_unset_multiple_file_upload( $field )
				: $this->maybe_revert_single_file_upload( $field );
		}
	}

}//end class
