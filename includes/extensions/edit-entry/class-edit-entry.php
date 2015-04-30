<?php
/**
 * The GravityView Edit Entry Extension
 *
 * Easily edit entries in GravityView.
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}


class GravityView_Edit_Entry {

	static $file;
	static $nonce_key;
	static $instance;

	/**
	 * Gravity Forms entry array
	 *
	 * @var array
	 */
	var $entry;

	/**
	 * Gravity Forms form array
	 *
	 * @var array
	 */
	var $form;

	/**
	 * ID of the current view
	 *
	 * @var int
	 */
	var $view_id;
	var $is_valid = NULL;

	function __construct() {

		self::$instance = &$this;

		self::$file = plugin_dir_path( __FILE__ );

		include_once( GRAVITYVIEW_DIR .'includes/class-admin-approve-entries.php' );

		// Stop Gravity Forms processing what is ours!
		add_filter( 'wp', array( $this, 'prevent_maybe_process_form'), 8 );

		add_filter( 'gravityview_is_edit_entry', array( $this, 'is_edit_entry') );

		add_action( 'gravityview_edit_entry', array( $this, 'init' ) );

		add_filter( 'gravityview_entry_default_fields', array( $this, 'add_default_field'), 10, 3 );

		// For the Edit Entry Link, you don't want visible to all users.
		add_filter( 'gravityview_field_visibility_caps', array( $this, 'modify_visibility_caps'), 10, 5 );

		// Add fields expected by GFFormDisplay::validate()
		add_filter( 'gform_pre_validation', array( $this, 'gform_pre_validation') );

		// Modify the field options based on the name of the field type
		add_filter( 'gravityview_template_edit_link_options', array( $this, 'edit_link_field_options' ), 10, 5 );

		// custom fields' options for zone EDIT
		add_filter( 'gravityview_template_field_options', array( $this, 'field_options' ), 10, 5 );

		// add template path to check for field
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

		// Add front-end access to Gravity Forms delete file action
		add_action('wp_ajax_nopriv_rg_delete_file', array('RGForms', 'delete_file'));

		// Make sure this hook is run for non-admins
		add_action('wp_ajax_rg_delete_file', array('RGForms', 'delete_file'));

		add_filter( 'gravityview_blacklist_field_types', array( $this, 'modify_field_blacklist' ), 10, 2 );

		add_filter( 'gravityview_tooltips', array( $this, 'tooltips') );

		// Process hooks for addons that may or may not be present
		$this->addon_specific_hooks();
	}

	/**
	 * Trigger hooks that are normally run in the admin for Addons, but need to be triggered manually because we're not in the admin
	 * @return void
	 */
	private function addon_specific_hooks() {

		if( class_exists( 'GFSignature') && is_callable( array('GFSignature', 'get_instance') ) ) {
			add_filter('gform_admin_pre_render', array(GFSignature::get_instance(), 'edit_lead_script'));
		}

	}

	/**
	 * Edit mode doesn't allow certain field types.
	 * @param  array $fields  Existing blacklist fields
	 * @param  string|null $context Context
	 * @return array          If not edit context, original field blacklist. Otherwise, blacklist including post fields.
	 */
	public function modify_field_blacklist( $fields = array(), $context = NULL ) {

		if( empty( $context ) || $context !== 'edit' ) {
			return $fields;
		}

		$add_fields = array(
			'post_image',
			'product',
			'quantity',
			'shipping',
			'total',
			'option',
			'coupon',
			'payment_status',
			'payment_date',
			'payment_amount',
			'is_fulfilled',
			'transaction_id',
			'transaction_type',
			// 'payment_method', This is editable in the admin, so allowing it here
		);

		return array_merge( $fields, $add_fields );
	}

	static function getInstance() {

		if( empty( self::$instance ) ) {
			self::$instance = new GravityView_Edit_Entry;
		}

		return self::$instance;
	}

	function setup_vars( $entry = null ) {
		$gravityview_view = GravityView_View::getInstance();

		if( empty( $entry ) ) {
			$entries = $gravityview_view->getEntries();
			$this->entry = $entries[0];
		} else {
			$this->entry = $entry;
		}

		$this->form = $gravityview_view->getForm();
		$this->form_id = $gravityview_view->getFormId();
		$this->view_id = $gravityview_view->getViewId();

		self::$nonce_key = sprintf( 'edit_%d_%d_%d', $this->view_id, $this->form_id, $this->entry['id'] );
	}

	/**
	 * The edit entry link creates a secure link with a nonce
	 *
	 * It also mimics the URL structure Gravity Forms expects to have so that
	 * it formats the display of the edit form like it does in the backend, like
	 * "You can edit this post from the post page" fields, for example.
	 *
	 * @filter default text
	 * @action default text
	 * @param  [type]      $entry [description]
	 * @param  [type]      $field [description]
	 * @return [type]             [description]
	 */
	static function get_edit_link( $entry, $field ) {

		self::getInstance()->setup_vars( $entry );

		$base = gv_entry_link( $entry );

		$url = add_query_arg( array(
			'page' => 'gf_entries', // Needed for GFForms::get_page()
			'view' => 'entry', // Needed for GFForms::get_page()
			'edit' => wp_create_nonce( self::$nonce_key )
		), $base );

		return $url;
	}

	/**
	 * Include this extension templates path
	 * @param array $file_paths List of template paths ordered
	 */
	function add_template_path( $file_paths ) {

		// Index 100 is the default GravityView template path.
		$file_paths[ 110 ] = self::$file;

		return $file_paths;
	}

	/**
	 * Add "Edit Link Text" setting to the edit_link field settings
	 * @param  [type] $field_options [description]
	 * @param  [type] $template_id   [description]
	 * @param  [type] $field_id      [description]
	 * @param  [type] $context       [description]
	 * @param  [type] $input_type    [description]
	 * @return [type]                [description]
	 */
	function edit_link_field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		// Always a link, never a filter
		unset( $field_options['show_as_link'], $field_options['search_filter'] );

		// Edit Entry link should only appear to visitors capable of editing entries
		unset( $field_options['only_loggedin'], $field_options['only_loggedin_cap'] );

		$add_option['edit_link'] = array(
			'type' => 'text',
			'label' => __( 'Edit Link Text', 'gravityview' ),
			'desc' => NULL,
			'value' => __('Edit Entry', 'gravityview'),
			'merge_tags' => true,
		);

		return array_merge( $add_option, $field_options );
	}

	/**
	 * Add tooltips
	 * @param  array $tooltips Existing tooltips
	 * @return array           Modified tooltips
	 */
	function tooltips( $tooltips ) {

		$return = $tooltips;

		$return['allow_edit_cap'] = array(
			'title' => __('Limiting Edit Access', 'gravityview'),
			'value' => __('Change this setting if you don\'t want the user who created the entry to be able to edit this field.', 'gravityview'),
		);

		return $return;
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

		// We only want to modify the settings for the edit context
		if( 'edit' !== $context ) {
			return $field_options;
		}

		//  Entry field is only for logged in users
		unset( $field_options['only_loggedin'], $field_options['only_loggedin_cap'] );

		$add_options = array(
			'allow_edit_cap' => array(
				'type' => 'select',
				'label' => __( 'Make field editable to:', 'gravityview' ),
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

		if( $zone !== 'edit' ) {

			$entry_default_fields['edit_link'] = array(
				'label' => __('Edit Entry', 'gravityview'),
				'type' => 'edit_link',
				'desc'	=> __('A link to edit the entry. Visible based on View settings.', 'gravityview'),
			);

		}

		return $entry_default_fields;
	}

	/**
	 * Add Edit Entry Link to the Add Field dialog
	 * @param array $available_fields
	 */
	function add_available_field( $available_fields = array() ) {

		$available_fields['edit_link'] = array(
			'label_text' => __( 'Edit Entry', 'gravityview' ),
			'field_id' => 'edit_link',
			'label_type' => 'field',
			'input_type' => 'edit_link',
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
		if( $context === 'edit' ) {

			// Remove other built-in caps.
			unset( $caps['publish_posts'], $caps['gravityforms_view_entries'], $caps['delete_others_posts'] );

			$caps['read'] = _x('Entry Creator','User capability', 'gravityview');
		}

		return $caps;
	}

	/**
	 * Force Gravity Forms to output scripts as if it were in the admin
	 * @return void
	 */
	function print_scripts( $css_only = false ) {
		$gravityview_view = GravityView_View::getInstance();

		wp_enqueue_style('gravityview-edit-entry', plugins_url('/assets/css/gv-edit-entry-admin.css', __FILE__ ), array(), GravityView_Plugin::version );

		if( $css_only ) { return; }

		wp_register_script( 'gform_gravityforms', GFCommon::get_base_url().'/js/gravityforms.js', array( 'jquery', 'gform_json', 'gform_placeholder', 'sack', 'plupload-all', 'gravityview-fe-view' ) );

		GFFormDisplay::enqueue_form_scripts($gravityview_view->getForm(), false);

		// Sack is required for images
		wp_print_scripts( array( 'sack', 'gform_gravityforms' ) );
	}

	/**
	 * Load required files and trigger edit flow
	 *
	 * Run when the is_edit_entry returns true.
	 *
	 * @param GravityView_View_Data $gv_data GravityView Data object
	 * @return void
	 */
	function init( $gv_data ) {

		require_once(GFCommon::get_base_path() . "/form_display.php");
		require_once(GFCommon::get_base_path() . "/entry_detail.php");
		require_once( self::$file . '/class-gv-gfcommon.php' );

		$this->setup_vars();

		// Multiple Views embedded, don't proceed if nonce fails
		if( $gv_data->has_multiple_views() && ! wp_verify_nonce( $_GET['edit'], self::$nonce_key ) ) {
			$this->print_scripts( true );
			return;
		}

		// Sorry, you're not allowed here.
		if( false === $this->user_can_edit_entry( true ) ) {
			$this->print_scripts( true );
			return;
		}

		$this->print_scripts();

		$this->process_save();

		$this->edit_entry_form();

	}

	/**
	 * Because we're mimicking being a front-end Gravity Forms form while using a Gravity Forms
	 * backend form, we need to prevent them from saving twice.
	 * @return void
	 */
	function prevent_maybe_process_form() {
		global $post;

		do_action('gravityview_log_debug', 'GravityView_Edit_Entry[prevent_maybe_process_form] $_POSTed data (sanitized): ', esc_html( print_r( $_POST, true ) ) );

		if( !empty( $_POST['is_gv_edit_entry'] ) && wp_verify_nonce( $_POST['is_gv_edit_entry'], 'is_gv_edit_entry' ) ) {
			remove_action('wp',  array('RGForms', 'maybe_process_form'), 9);
		}
	}


	function process_save() {

		// If the form is submitted
		if( RGForms::post("action") === "update") {

			// Make sure the entry, view, and form IDs are all correct
			$this->verify_nonce();

			if( $this->entry['id'] !== $_POST['lid'] ) {
				return;
			}

			do_action('gravityview_log_debug', 'GravityView_Edit_Entry[process_save] $_POSTed data (sanitized): ', esc_html( print_r( $_POST, true ) ) );

			$lead_id = absint( $_POST['lid'] );

			//Loading files that have been uploaded to temp folder
			$files = GFCommon::json_decode( stripslashes( RGForms::post( "gform_uploaded_files" ) ) );

			if( !is_array( $files ) ) {
			 	$files = array();
			}


			// When Gravity Forms validates upload fields, they expect this variable to be set.
			GFFormsModel::$uploaded_files[ $this->form_id ] = $files;


			$this->validate();


			if( $this->is_valid ) {

				do_action('gravityview_log_debug', 'GravityView_Edit_Entry[process_save] Submission is valid.' );

				/**
				 * @hack This step is needed to unset the adminOnly from form fields
				 */
				$form = $this->form_prepare_for_save();

				// Make sure hidden fields are represented in $_POST
				$this->combine_update_existing();

				/**
				 * @hack to avoid the capability validation of the method save_lead for GF 1.9+
				 */
				if( isset( $_GET['page'] ) && isset( GFForms::$version ) && version_compare( GFForms::$version, '1.9', '>=' ) ) {
					unset( $_GET['page'] );
				}

				GFFormsModel::save_lead( $form, $this->entry );


				// If there's a post associated with the entry, process post fields
				if( !empty( $this->entry['post_id'] ) ) {

					$this->maybe_update_post_fields( $form );

				}

				do_action("gform_after_update_entry", $this->form, $this->entry["id"]);
				do_action("gform_after_update_entry_{$this->form["id"]}", $this->form, $this->entry["id"]);

				// Re-define the entry now that we've updated it.
				$this->entry = RGFormsModel::get_lead( $this->entry["id"] );

				$this->entry = GFFormsModel::set_entry_meta( $this->entry, $this->form );

				// We need to clear the cache because Gravity Forms caches the field values, which
				// we have just updated.
				foreach ($this->form['fields'] as $key => $field) {
					GFFormsModel::refresh_lead_field_value( $this->entry['id'], $field['id'] );
				}

				/**
				 * Perform an action after the entry has been updated using Edit Entry
				 *
				 * @param array $form Gravity Forms form array
				 * @param string $entry_id Numeric ID of the entry that was updated
				 */
				do_action( 'gravityview/edit_entry/after_update', $this->form, $this->entry['id'] );
			}
		} // endif action is update.

	} // process_save

	/**
	 * Loop through the fields being edited and if they include Post fields, update the Entry's post object
	 *
	 * @param array $form Gravity Forms form
	 *
	 * @return void
	 */
	function maybe_update_post_fields( $form ) {

		$post_id = $this->entry['post_id'];

		// Security check
		if( false === current_user_can( 'edit_post', $post_id ) ) {
			do_action( 'gravityview_log_error', 'The current user does not have the ability to edit Post #'.$post_id );
			return;
		}

		$updated_post = $original_post = get_post( $post_id );

		foreach ( $this->entry as $field_id => $value ) {

			$field = RGFormsModel::get_field( $form, $field_id );

			if( class_exists('GF_Fields') ) {
				$field = GF_Fields::create( $field );
			}

			if( GFCommon::is_post_field( $field ) ) {

				// Get the value of the field, including $_POSTed value
				$value = RGFormsModel::get_field_value( $field );

				// Convert the field object in 1.9 to an array for backward compatibility
				$field_array = GVCommon::get_field_array( $field );

				switch( $field_array['type'] ) {

					case 'post_title':
					case 'post_content':
					case 'post_excerpt':
						$updated_post->{$field_array['type']} = $value;
						break;
					case 'post_tags':
						wp_set_post_tags( $post_id, $value, false );
						break;
					case 'post_category':

						$value = is_array( $value ) ? array_values( $value ) : (array)$value;
						$value = array_filter( $value );

						wp_set_post_categories( $post_id, $value, false );

						break;
					case 'post_custom_field':

						$input_type = RGFormsModel::get_input_type( $field );
						$custom_field_name = $field_array['postCustomFieldName'];

						// Only certain custom field types are supported
						if( !in_array( $input_type, array( 'list', 'fileupload' ) ) ) {
							update_post_meta( $post_id, $custom_field_name, $value );
						}

						break;

				}
			}

			continue;
		}

		$return_post = wp_update_post( $updated_post, true );

		if( is_wp_error( $return_post ) ) {
			do_action( 'gravityview_log_error', 'Updating the post content failed', $return_post );
		} else {
			do_action( 'gravityview_log_debug', 'Updating the post content for post #'.$post_id.' succeeded' );
		}

	}

	/**
	 * Gets stored entry data and combines it in to $_POST array.
	 *
	 * Reason: If a form field doesn't exist in the $_POST data,
	 * its value will be cleared from the DB. Since some form
	 * fields could be hidden, we need to make sure existing
	 * vales are passed through $_POST.
	 *
	 * @access public
	 * @param int $view_id
	 * @param array $entry
	 * @return void
	 */
	private function combine_update_existing() {

		// Get the original form, not modified form stored in the class
	    $form = gravityview_get_form( $this->form['id'] );

	    foreach ( $this->entry as $field_id => $value ) {

	    	$field = RGFormsModel::get_field( $form, $field_id );

	    	// Get the value of the field, including $_POSTed value
	    	$value = RGFormsModel::get_field_value( $field );

	    	$posted_entry[ $field_id ] = ( is_array( $value ) && isset( $value[ $field_id ] ) ) ? $value[ $field_id ] : $value;

	    	continue;
	    }

	    // Remove empty
	    $posted_entry = array_filter( $posted_entry );

	    // If the field doesn't exist, merge it in to $_POST
	    $_POST = array_merge( $posted_entry, $_POST );

	}

	/**
	 * Unset adminOnly and convert field input key to string
	 * @return array $form
	 */
	private function form_prepare_for_save() {
		$form = $this->form;

		foreach( $form['fields'] as &$field ) {

			// GF 1.9+
			if( is_object( $field ) ) {
				$field->adminOnly = '';
			} else {
				$field['adminOnly'] = '';
			}

			if( isset($field["inputs"] ) && is_array( $field["inputs"] ) ) {
				foreach( $field["inputs"] as $key => $input ) {

					// GF 1.9+
					if( is_object( $field ) ) {
						$field->inputs[ $key ][ 'id' ] = (string)$input['id'];
					} else {
						$field["inputs"][ $key ]['id'] = (string)$input['id'];
					}
				}
			}

		}
		return $form;
	}

	/**
	 * Add field keys that Gravity Forms expects.
	 *
	 * @see GFFormDisplay::validate()
	 * @param  array $form GF Form
	 * @return array       Modified GF Form
	 */
	function gform_pre_validation( $form ) {

		if( ! $this->verify_nonce() ) {
			return $form;
		}

		// Fix PHP warning regarding undefined index.
		foreach ($form['fields'] as &$field) {

			// This is because we're doing admin form pretending to be front-end, so Gravity Forms
			// expects certain field array items to be set.
			foreach ( array( 'noDuplicates', 'adminOnly', 'inputType', 'isRequired', 'enablePrice', 'inputs', 'allowedExtensions' ) as $key ) {
				$field[ $key ] = isset( $field[ $key ] ) ? $field[ $key ] : NULL;
			}

			// unset emailConfirmEnabled for email type fields
			if( 'email' === $field['type'] && !empty( $field['emailConfirmEnabled'] ) ) {
				$field['emailConfirmEnabled'] = '';
			}

			switch( RGFormsModel::get_input_type( $field ) ) {

				/**
				 * this whole fileupload hack is because in the admin, Gravity Forms simply doesn't update any fileupload field if it's empty, but it DOES in the frontend.
				 *
				 * What we have to do is set the value so that it doesn't get overwritten as empty on save and appears immediately in the Edit Entry screen again.
				 *
				 * @hack
				 */
				case 'fileupload':
				case 'post_image':

					// Set the previous value
					$entry = $this->get_entry();

					$input_name = 'input_'.$field['id'];
					$form_id = $form['id'];

					$value = NULL;

					// Use the previous entry value as the default.
					if( isset( $entry[ $field['id'] ] ) ) {
						$value = $entry[ $field['id'] ];
					}

					// If this is a single upload file
					if( !empty( $_FILES[ $input_name ] ) && !empty( $_FILES[ $input_name ]['name'] ) ) {
						$file_path = GFFormsModel::get_file_upload_path( $form['id'], $_FILES[ $input_name ]['name'] );
						$value = $file_path['url'];

					} else {

						// Fix PHP warning on line 1498 of form_display.php for post_image fields
						// Fix PHP Notice:  Undefined index:  size in form_display.php on line 1511
						$_FILES[ $input_name ] = array('name' => '', 'size' => '' );

					}

					if( rgar($field, "multipleFiles") ) {

						// If there are fresh uploads, process and merge them.
						// Otherwise, use the passed values, which should be json-encoded array of URLs
						if( isset( GFFormsModel::$uploaded_files[$form_id][$input_name] ) ) {

							$value = empty( $value ) ? '[]' : $value;
							$value = stripslashes_deep( $value );
							$value = GFFormsModel::prepare_value( $form, $field, $value, $input_name, $entry['id'], array());
						}

					} else {

						// A file already exists when editing an entry
						// We set this to solve issue when file upload fields are required.
						GFFormsModel::$uploaded_files[ $form_id ][ $input_name ] = $value;

					}

					$_POST[ $input_name ] = $value;

					break;
				case 'number':
					// Fix "undefined index" issue at line 1286 in form_display.php
					if( !isset( $_POST['input_'.$field['id'] ] ) ) {
						$_POST['input_'.$field['id'] ] = NULL;
					}
					break;
				case 'captcha':
					// Fix issue with recaptcha_check_answer() on line 1458 in form_display.php
					$_POST['recaptcha_challenge_field'] = NULL;
					$_POST['recaptcha_response_field'] = NULL;
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
	function validate() {

		/**
		 * For some crazy reason, Gravity Forms doesn't validate Edit Entry form submissions.
		 * You can enter whatever you want!
		 * We try validating, and customize the results using `self::custom_validation()`
		 */
		add_filter( 'gform_validation_'.$this->form_id, array( &$this, 'custom_validation'), 10, 4);

		// Needed by the validate funtion
		$failed_validation_page = NULL;
		$field_values = RGForms::post("gform_field_values"); // this returns empty!!!

		// Prevent entry limit from running when editing an entry, also
		// prevent form scheduling from preventing editing
		unset( $this->form['limitEntries'], $this->form['scheduleForm'] );

	    // Get all fields for form
	    $view_data = GravityView_View_Data::getInstance();
	    $properties = $view_data->get_fields( $this->view_id );

	    // If edit tab not yet configured, show all fields
	    $edit_fields = !empty( $properties['edit_edit-fields'] ) ? $properties['edit_edit-fields'] : NULL;

	    // Hide fields depending on Edit Entry settings
		$this->form['fields'] = $this->filter_fields( $this->form['fields'], $edit_fields );

		$this->is_valid = GFFormDisplay::validate( $this->form, $field_values, 1, $failed_validation_page );

		remove_filter( 'gform_validation_'.$this->form_id, array( &$this, 'custom_validation'), 10 );
	}

	/**
	 * A modified version of the Gravity Form method.
	 * Generates the form responsible for editing a Gravity
	 * Forms entry.
	 *
	 * @access public
	 * @param array $fields
	 * @param array $properties
	 * @return void
	 */
	private function lead_detail_edit( $form, $lead, $view_id ){

	    $form = apply_filters( "gform_admin_pre_render_" . $form["id"], apply_filters( "gform_admin_pre_render", $form ) );
	    $form_id = $form["id"];
	    ?>
	    <div class="postbox">
	        <h3>
	                <label for="name"><?php esc_html_e( 'Details', 'gravityview' ); ?></label>
	        </h3>
	        <div class="inside">
	            <table class="form-table entry-details">
	                <tbody>
	                <?php

	                // Get all fields for form
	                $properties = GravityView_View_Data::getInstance()->get_fields( $view_id );

	                // If edit tab not yet configured, show all fields
	                $edit_fields = !empty( $properties['edit_edit-fields'] ) ? $properties['edit_edit-fields'] : NULL;

	                // Hide fields depending on admin settings
	                $fields = $this->filter_fields( $form['fields'], $edit_fields );

	                foreach( $fields as $field ){

	                    $td_id = "field_" . $form_id . "_" . $field['id'];
	                    $value = RGFormsModel::get_lead_field_value( $lead, $field );
	                    $label = esc_html( GFCommon::get_label( $field ) );
	                    $input = GV_GFCommon::get_field_input( $field, $value, $lead['id'], $form_id );
	                    $error_class = rgget( 'failed_validation', $field ) ? "gfield_error" : "";

	                    $validation_message = ( rgget('failed_validation', $field ) && !empty( $field['validation_message'] ) ) ? sprintf("<div class='gfield_description validation_message'>%s</div>", $field['validation_message'] ) : '';

	                    if( rgar( $field, 'descriptionPlacement') == 'above' ) {
	                        $input = $validation_message . $input;
	                    } else {
	                        $input = $input . $validation_message;
	                    }

	                    //Add required indicator
	                    $required = ( !empty( $field['isRequired'] ) ) ? '<span class="required">*</span>' : '';

	                    // custom class as defined on field details
	                    $custom_class = empty( $field['gvCustomClass'] ) ? '' : ' class="'. esc_attr( $field['gvCustomClass'] ) .'"';

	                    switch( RGFormsModel::get_input_type( $field ) ){

	                        case 'section' :
	                            ?>
	                            <tr valign="top"<?php echo $custom_class; ?>>
	                                    <td class="detail-view">
	                                            <div style="margin-bottom:10px; border-bottom:1px dotted #ccc;"><h2 class="detail_gsection_title"><?php echo $label; ?></h2></div>
	                                    </td>
	                            </tr>
	                            <?php

	                        break;

	                        case 'captcha':
	                        case 'html':
	                        case 'password':
	                            //ignore certain fields
	                        break;

	                        default :

	                            $content =
	                                '<tr valign="top"'. $custom_class .'>
	                                    <td class="detail-view '.$error_class.'" id="'. $td_id .'">
	                                        <label class="detail-label">' . $label . $required . '</label>' . $input . '
	                                    </td>
	                                </tr>';

		                        /**
		                         * Modify the Edit Entry field content
		                         *
		                         * @param string $content Field HTML as rendered in the Edit Entry form
		                         * @param array $field Gravity Forms field array, with extra GravityView keys such as `gvCustomClass`
		                         * @param string $value Value of the field
		                         * @param int $entry_id Entry ID
		                         * @param int $form_id Form ID
		                         *
		                         * @return string HTML output for the field in the Edit Entry form
		                         */
	                            $content = apply_filters( 'gravityview_edit_entry_field_content', $content, $field, $value, $lead['id'], $form['id'] );

	                            echo $content;
	                        break;
	                    }
	                }
	                ?>
	                </tbody>
	            </table>
	            <br/>
	            <div class="gform_footer">
	                <input type="hidden" name="gform_unique_id" value="" />
	                <input type="hidden" name="gform_uploaded_files" id="gform_uploaded_files_<?php echo $form_id; ?>" value="" />
	            </div>
	        </div>
	    </div>
	    <?php
	}

	/**
	 * Filter area fields based on specified conditions
	 *
	 * @uses GravityView_Edit_Entry::user_can_edit_field() Check caps
	 * @access private
	 * @param array $fields
	 * @param array $configured_fields
	 * @since  1.5
	 * @return array $fields
	 */
	private function filter_fields( $fields, $configured_fields ) {

	    if( empty( $fields ) || !is_array( $fields ) ) {
	        return $fields;
	    }

	    $edit_fields = array();


	    // The Edit tab has not been configured, so we return all fields by default.
	    if( empty( $configured_fields ) ) {
	    	return $fields;
	    }

	    // The edit tab has been configured, so we loop through to configured settings
		foreach ( $configured_fields as $configured_field ) {

		    foreach ( $fields as $field ) {

		    	if( intval( $configured_field['id'] ) === intval( $field['id'] ) ){

		    		if( $this->user_can_edit_field( $configured_field, false ) ) {
		               $edit_fields[] = $this->merge_field_properties( $field, $configured_field );
		            }

		        }

		    }

		}

	    return $edit_fields;

	}

	/**
	 * Override GF Form field properties with the ones defined on the View
	 * @param  array $field GF Form field object
	 * @param  array $setting  GV field options
	 * @since  1.5
	 * @return array
	 */
	private function merge_field_properties( $field, $field_setting ) {

		$return_field = $field;

	    if( empty( $field_setting['show_label'] ) ) {
	        $return_field['label'] = '';
	    } elseif ( !empty( $field_setting['custom_label'] ) ) {
	        $return_field['label'] = $field_setting['custom_label'];
	    }

	    if( !empty( $field_setting['custom_class'] ) ) {
	         $return_field['gvCustomClass'] = gravityview_sanitize_html_class( $field_setting['custom_class'] );
	    }

		// @since 1.6
		// Normalise page numbers - avoid conflicts with page validation
		$return_field['pageNumber'] = 1;

	    return $return_field;

	}

	/**
	 * Make validation work for Edit Entry
	 *
	 * Because we're calling the GFFormDisplay::validate() in an unusual way (as a front-end
	 * form pretending to be a back-end form), validate() doesn't know we _can't_ edit post
	 * fields. This goes through all the fields and if they're an invalid post field, we
	 * set them as valid. If there are still issues, we'll return false.
	 *
	 * @param  [type] $validation_results [description]
	 * @return [type]                     [description]
	 */
	function custom_validation( $validation_results ) {

		do_action('gravityview_log_debug', 'GravityView_Edit_Entry[custom_validation] Validation results: ', $validation_results );

		do_action('gravityview_log_debug', 'GravityView_Edit_Entry[custom_validation] $_POSTed data (sanitized): ', esc_html( print_r( $_POST, true ) ) );

		$gv_valid = true;

		foreach ($validation_results['form']['fields'] as $key => &$field ) {

			// This field has failed validation.
			if( !empty( $field['failed_validation'] ) ) {

				$value = RGFormsModel::get_field_value( $field );

				do_action('gravityview_log_debug', 'GravityView_Edit_Entry[custom_validation] Field is invalid.', array( 'field' => $field, 'value' => $value ) );

				switch ( RGFormsModel::get_input_type( $field ) ) {

					// Captchas don't need to be re-entered.
					case 'captcha':

					// Post Image fields aren't editable, so we un-fail them.
					case 'post_image':
						$field['failed_validation'] = false;
						unset( $field['validation_message'] );
						break;

					case 'fileupload':
						// in case nothing is uploaded but there are already files saved
						if( !empty( $field['isRequired'] ) && !empty( $value ) ) {
							$field['failed_validation'] = false;
							unset( $field['validation_message'] );
						}

						break;
				}



				// You can't continue inside a switch, so we do it after.
				if( empty( $field['failed_validation'] ) ) {
					continue;
				}

				// checks if the No Duplicates option is not validating entry against itself, since
				// we're editing a stored entry, it would also assume it's a duplicate.
				if( !empty( $field['noDuplicates'] ) ) {

					$entry = $this->get_entry();

					// If the value of the entry is the same as the stored value
					// Then we can assume it's not a duplicate, it's the same.
					if( !empty( $entry ) && $value == $entry[ $field['id'] ] ) {
						//if value submitted was not changed, then don't validate
						$field['failed_validation'] = false;

						unset( $field['validation_message'] );

						do_action('gravityview_log_debug', 'GravityView_Edit_Entry[custom_validation] Field not a duplicate; it is the same entry.', $entry );

						continue;
					}
				}

				$gv_valid = false;

			}


			switch ( RGFormsModel::get_input_type( $field ) ) {

				// validate if multi file upload reached max number of files [maxFiles] => 2
				case 'fileupload' :

					if( rgar( $field, 'maxFiles') && rgar( $field, 'multipleFiles') ) {

						$input_name = 'input_' . $field['id'];
						//uploaded
						$file_names = isset( GFFormsModel::$uploaded_files[ $validation_results['form']['id'] ][ $input_name ] ) ? GFFormsModel::$uploaded_files[ $validation_results['form']['id'] ][ $input_name ] : array();

						//existent
						$entry = $this->get_entry();
						$value = NULL;
						if( isset( $entry[ $field['id'] ] ) ) {
							$value = json_decode( $entry[ $field['id'] ], true );
						}

						// count uploaded files and existent entry files
						$count_files = count( $file_names ) + count( $value );

						if( $count_files > $field['maxFiles'] ) {
							$field['validation_message'] = __( 'Maximum number of files reached', 'gravityview' );
							$field['failed_validation'] = 1;
							$gv_valid = false;
						}

					}

				break;

			}

		}

		$validation_results['is_valid'] = $gv_valid;

		do_action('gravityview_log_debug', 'GravityView_Edit_Entry[custom_validation] Validation results.', $validation_results );

		return $validation_results;
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

	/**
	 * Is the current page an Edit Entry page?
	 * @return boolean
	 */
	public function is_edit_entry() {

		$gf_page = ( 'entry' === RGForms::get("view") );

		return ( $gf_page && isset( $_GET['edit'] ) || RGForms::post("action") === "update" );
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
		if( ! $this->is_edit_entry() ) { return false; }

		return wp_verify_nonce( $_GET['edit'], self::$nonce_key );

	}

	/**
	 * Check whether a field is editable by the current user, and optionally display an error message
	 * @uses  GravityView_Edit_Entry->check_user_cap_edit_field() Check user capabilities
	 * @param  array  $field Field or field settings array
	 * @param  boolean $echo  Whether to show error message telling user they aren't allowed
	 * @return boolean         True: user can edit the current field; False: nope, they can't.
	 */
	private function user_can_edit_field( $field, $echo = false ) {

		$error = NULL;

		if( ! $this->check_user_cap_edit_field( $field ) ) {
			$error = __( 'You do not have permission to edit this field.', 'gravityview');
		}

		// No errors; everything's fine here!
		if( empty( $error ) ) {
			return true;
		}

		if( $echo ) {
			echo self::getInstance()->generate_notice( wpautop( esc_html( $error ) ), 'gv-error error');
		}

		do_action('gravityview_log_error', 'GravityView_Edit_Entry[user_can_edit_field]' . $error );

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
		if( GFCommon::current_user_can_any( 'gravityforms_edit_entries' ) ) {
			return true;
		}

		$field_cap = isset( $field['allow_edit_cap'] ) ? $field['allow_edit_cap'] : false;

		// If the field has custom editing capaibilities set, check those
		if( $field_cap ) {
			return GFCommon::current_user_can_any( $field['allow_edit_cap'] );
		}

		return false;
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
	function user_can_edit_entry( $echo = false ) {

		$error = NULL;

		/**
		 *  1. Permalinks are turned off
		 *  2. There are two entries embedded using oEmbed
		 *  3. One of the entries has just been saved
		 */
		if( !empty( $_POST['lid'] ) && !empty( $_GET['entry'] ) && ( $_POST['lid'] !== $_GET['entry'] ) ) {

			$error = true;

		}

		if( !empty( $_GET['entry'] ) && (string)$this->entry['id'] !== $_GET['entry'] ) {

			$error = true;

		} elseif( ! $this->verify_nonce() ) {

			/**
			 * If the Entry is embedded, there may be two entries on the same page.
			 * If that's the case, and one is being edited, the other should fail gracefully and not display an error.
			 */
			if( GravityView_oEmbed::getInstance()->get_entry_id() ) {
				$error = true;
			} else {
				$error = __( 'The link to edit this entry is not valid; it may have expired.', 'gravityview');
			}

		}

		if( ! self::check_user_cap_edit_entry( $this->entry ) ) {
			$error = __( 'You do not have permission to edit this entry.', 'gravityview');
		}

		if( $this->entry['status'] === 'trash' ) {
			$error = __('You cannot edit the entry; it is in the trash.', 'gravityview' );
		}

		// No errors; everything's fine here!
		if( empty( $error ) ) {
			return true;
		}

		if( $echo && $error !== true ) {
			echo $this->generate_notice( wpautop( esc_html( $error ) ), 'gv-error error');
		}

		do_action('gravityview_log_error', 'GravityView_Edit_Entry[user_can_edit_entry]' . $error );

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
	public static function check_user_cap_edit_entry( $entry ) {
		$gravityview_view = GravityView_View::getInstance();

		// Or if they can edit any entries (as defined in Gravity Forms), we're good.
		if( GFCommon::current_user_can_any( 'gravityforms_edit_entries' ) ) {
			return true;
		}

		if( !isset( $entry['created_by'] ) ) {

			do_action('gravityview_log_error', 'GravityView_Edit_Entry[check_user_cap_edit_entry] Entry `created_by` doesn\'t exist.');

			return false;
		}

		$user_edit = $gravityview_view->getAtts('user_edit');
		$current_user = wp_get_current_user();

		if( empty( $user_edit ) ) {

			do_action('gravityview_log_debug', 'GravityView_Edit_Entry[check_user_cap_edit_entry] User Edit is disabled. Returning false.' );

			return false;
		}

		// If the logged-in user is the same as the user who created the entry, we're good.
		if( is_user_logged_in() && intval( $current_user->ID ) === intval( $entry['created_by'] ) ) {

			do_action('gravityview_log_debug', sprintf( 'GravityView_Edit_Entry[check_user_cap_edit_entry] User %s created the entry.', $current_user->ID ) );

			return true;
		}

		return false;
	}


	function generate_notice( $notice, $class = '' ) {
		return '<div class="gv-notice '.esc_attr( $class ) .'">'. $notice .'</div>';
	}

	/**
	 * Get the posted values from the edit form submission
	 *
	 * @hack
	 * @uses GFFormsModel::get_field_value()
	 * @param  mixed $value Existing field value, before edit
	 * @param  array $lead  Gravity Forms entry array
	 * @param  array $field Gravity Forms field array
	 * @return string        [description]
	 */
	public function get_field_value( $value, $lead, $field ) {

		// The form's not being edited; use the original value
		if( empty( $_POST['is_gv_edit_entry'] ) ) {
			return $value;
		}

		return GFFormsModel::get_field_value( $field, $lead, true );
	}

	/**
	 * Display the Edit Entry form
	 *
	 * @filter gravityview_edit_entry_title Modfify the edit entry title
	 * @return [type] [description]
	 */
	public function edit_entry_form() {

		$back_link = esc_url( remove_query_arg( array( 'page', 'view', 'edit' ) ) );

		?>

		<div class="gv-edit-entry-wrapper">

			<?php include_once( self::$file .'/inline-javascript.php'); ?>

			<h2 class="gv-edit-entry-title">
				<span><?php echo esc_attr( apply_filters('gravityview_edit_entry_title', __('Edit Entry', 'gravityview'), $this ) ); ?></span>
			</h2>

			<?php

			// Display the sucess message
			if( rgpost('action') === 'update' ) {

				if( ! $this->is_valid ){

					// Keeping this compatible with Gravity Forms.
				    $validation_message = "<div class='validation_error'>" . __('There was a problem with your submission.', 'gravityview') . " " . __('Errors have been highlighted below.', 'gravityview') . "</div>";
				    $message = apply_filters("gform_validation_message_{$this->form['id']}", apply_filters("gform_validation_message", $validation_message, $this->form), $this->form);

				    echo $this->generate_notice( $message , 'gv-error' );

				} else {
					$entry_updated_message = sprintf( esc_attr__('Entry Updated. %sReturn to Entry%s', 'gravityview'), '<a href="'. $back_link .'">', '</a>' );

					/**
					 * @since 1.5.4
					 * @param string $entry_updated_message Existing message
					 * @param int $view_id View ID
					 * @param array $entry Gravity Forms entry array
					 * @param string $back_link URL to return to the original entry. @since 1.6
					 */
					$message = apply_filters( 'gravityview/edit_entry/success', $entry_updated_message , $this->view_id, $this->entry, $back_link );

					echo $this->generate_notice( $message );
				}

			}

			?>

			<?php // The ID of the form needs to be `gform_{form_id}` for the pluploader ?>

			<form method="post" id="gform_<?php echo $this->form_id; ?>" enctype="multipart/form-data">

				<?php

				wp_nonce_field( self::$nonce_key, self::$nonce_key );

				wp_nonce_field( 'is_gv_edit_entry', 'is_gv_edit_entry', false );

				// Most of this is needed for GFFormDisplay::validate(), but `gform_unique_id` is needed for file cleanup.

				?>

				<input type="hidden" name="action" id="action" value="update" />
				<input type="hidden" class="gform_hidden" name="is_submit_<?php echo $this->form_id; ?>" value="1" />
				<input type="hidden" class="gform_hidden" name="gform_submit" value="<?php echo $this->form_id; ?>" />
				<input type="hidden" class="gform_hidden" name="gform_unique_id" value="<?php echo esc_attr( GFFormsModel::get_form_unique_id( $this->form_id ) ); ?>" />
				<input type="hidden" class="gform_hidden" name="state_<?php echo $this->form_id; ?>" value="<?php  echo GFFormDisplay::get_state( $this->form, NULL ); ?>" />
				<input type="hidden" name="gform_field_values" value="" />
				<input type="hidden" name="screen_mode" id="screen_mode" value="view" />
				<input type="hidden" name="lid" value="<?php echo $this->entry['id']; ?>" />

				<?php

				/**
				 * By default, the lead_detail_edit method uses the `RGFormsModel::get_lead_field_value()` method, which doesn't fill in $_POST values when there is a validation error, because it was designed to work in the admin. We want to use the `RGFormsModel::get_field_value()` If the form has been submitted, use the values for the fields.
				*/
				add_filter( 'gform_get_field_value', array( $this, 'get_field_value' ), 10, 3 );

				// Print the actual form HTML
				$this->lead_detail_edit( $this->form, $this->entry, $this->view_id );

				?>
				<div id="publishing-action">
					<?php

					/**
					 * @since 1.5.1
					 */
					do_action( 'gravityview/edit-entry/publishing-action/before', $this->form, $this->entry, $this->view_id );

					?>
				    <input class="btn btn-lg button button-large button-primary" type="submit" tabindex="4" value="<?php esc_attr_e( 'Update', 'gravityview'); ?>" name="save" />

		            <a class="btn btn-sm button button-small" tabindex="5" href="<?php echo $back_link ?>"><?php esc_attr_e( 'Cancel', 'gravityview' ); ?></a>
		            <?php

		            /**
		             * @since 1.5.1
		             */
		            do_action( 'gravityview/edit-entry/publishing-action/after', $this->form, $this->entry, $this->view_id );

		            ?>
				</div>

			</form>

			<?php GFFormDisplay::footer_init_scripts( $this->form_id ); ?>

		</div>

		<?php
	}


} // end class

//add_action( 'plugins_loaded', array('GravityView_Edit_Entry', 'getInstance'), 6 );
new GravityView_Edit_Entry;

