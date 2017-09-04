<?php
/**
 * GravityView Edit Entry - render frontend
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

class GravityView_Edit_Entry_Render {

    /**
     * @var GravityView_Edit_Entry
     */
    protected $loader;

	/**
	 * @var string String used to generate unique nonce for the entry/form/view combination. Allows access to edit page.
	 */
    static $nonce_key;

	/**
	 * @since 1.9
	 * @var string String used for check valid edit entry form submission. Allows saving edit form values.
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
	 * Gravity Forms entry array (it won't get changed during this class lifecycle)
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
     * @since 1.16.2.1
     * @var array
     */
    private static $original_form;

    /**
     * Gravity Forms form array after the form validation process
     * @since 1.13
     * @var array
     */
	public $form_after_validation = null;

    /**
     * Hold an array of GF field objects that have calculation rules
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
     * Updated entry is valid (GF Validation object)
     *
     * @var array
     */
	public $is_valid = NULL;

    function __construct( GravityView_Edit_Entry $loader ) {
        $this->loader = $loader;
    }

    function load() {

        /** @define "GRAVITYVIEW_DIR" "../../../" */
        include_once( GRAVITYVIEW_DIR .'includes/class-admin-approve-entries.php' );

        // Don't display an embedded form when editing an entry
        add_action( 'wp_head', array( $this, 'prevent_render_form' ) );
        add_action( 'wp_footer', array( $this, 'prevent_render_form' ) );

        // Stop Gravity Forms processing what is ours!
        add_filter( 'wp', array( $this, 'prevent_maybe_process_form'), 8 );

        add_filter( 'gravityview_is_edit_entry', array( $this, 'is_edit_entry') );

        add_action( 'gravityview_edit_entry', array( $this, 'init' ) );

        // Disable conditional logic if needed (since 1.9)
        add_filter( 'gform_has_conditional_logic', array( $this, 'manage_conditional_logic' ), 10, 2 );

        // Make sure GF doesn't validate max files (since 1.9)
        add_filter( 'gform_plupload_settings', array( $this, 'modify_fileupload_settings' ), 10, 3 );

        // Add fields expected by GFFormDisplay::validate()
        add_filter( 'gform_pre_validation', array( $this, 'gform_pre_validation') );

		// Fix multiselect value for GF 2.2
		add_filter( 'gravityview/edit_entry/field_value_multiselect', array( $this, 'fix_multiselect_value_serialization' ), 10, 3 );
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
        if( $this->is_edit_entry() ) {
            if( 'wp_head' === current_filter() ) {
                add_filter( 'gform_shortcode_form', '__return_empty_string' );
            } else {
                remove_filter( 'gform_shortcode_form', '__return_empty_string' );
            }
        }
    }

    /**
     * Because we're mimicking being a front-end Gravity Forms form while using a Gravity Forms
     * backend form, we need to prevent them from saving twice.
     * @return void
     */
    public function prevent_maybe_process_form() {

        if( ! empty( $_POST ) ) {
	        do_action( 'gravityview_log_debug', 'GravityView_Edit_Entry[prevent_maybe_process_form] $_POSTed data (sanitized): ', esc_html( print_r( $_POST, true ) ) );
        }

        if( $this->is_edit_entry_submission() ) {
            remove_action( 'wp',  array( 'RGForms', 'maybe_process_form'), 9 );
	        remove_action( 'wp',  array( 'GFForms', 'maybe_process_form'), 9 );
        }
    }

    /**
     * Is the current page an Edit Entry page?
     * @return boolean
     */
    public function is_edit_entry() {

        $is_edit_entry = GravityView_frontend::is_single_entry() && ! empty( $_GET['edit'] );

        return ( $is_edit_entry || $this->is_edit_entry_submission() );
    }

	/**
	 * Is the current page an Edit Entry page?
	 * @since 1.9
	 * @return boolean
	 */
	public function is_edit_entry_submission() {
		return !empty( $_POST[ self::$nonce_field ] );
	}

    /**
     * When Edit entry view is requested setup the vars
     */
    private function setup_vars() {
        $gravityview_view = GravityView_View::getInstance();


        $entries = $gravityview_view->getEntries();
	    self::$original_entry = $entries[0];
	    $this->entry = $entries[0];

        self::$original_form = $gravityview_view->getForm();
        $this->form = $gravityview_view->getForm();
        $this->form_id = $gravityview_view->getFormId();
        $this->view_id = $gravityview_view->getViewId();

        self::$nonce_key = GravityView_Edit_Entry::get_nonce_key( $this->view_id, $this->form_id, $this->entry['id'] );
    }


    /**
     * Load required files and trigger edit flow
     *
     * Run when the is_edit_entry returns true.
     *
     * @param GravityView_View_Data $gv_data GravityView Data object
     * @return void
     */
    public function init( $gv_data ) {

        require_once( GFCommon::get_base_path() . '/form_display.php' );
        require_once( GFCommon::get_base_path() . '/entry_detail.php' );

        $this->setup_vars();

        // Multiple Views embedded, don't proceed if nonce fails
		$multiple_views = defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ? gravityview()->views->count() > 1 : $gv_data->has_multiple_views();
        if( $multiple_views && ! wp_verify_nonce( $_GET['edit'], self::$nonce_key ) ) {
            do_action('gravityview_log_error', __METHOD__ . ': Nonce validation failed for the Edit Entry request; returning' );
            return;
        }

        // Sorry, you're not allowed here.
        if( false === $this->user_can_edit_entry( true ) ) {
            do_action('gravityview_log_error', __METHOD__ . ': User is not allowed to edit this entry; returning', $this->entry );
            return;
        }

        $this->print_scripts();

        $this->process_save();

        $this->edit_entry_form();

    }


    /**
     * Force Gravity Forms to output scripts as if it were in the admin
     * @return void
     */
    private function print_scripts() {
        $gravityview_view = GravityView_View::getInstance();

        wp_register_script( 'gform_gravityforms', GFCommon::get_base_url().'/js/gravityforms.js', array( 'jquery', 'gform_json', 'gform_placeholder', 'sack', 'plupload-all', 'gravityview-fe-view' ) );

        GFFormDisplay::enqueue_form_scripts($gravityview_view->getForm(), false);

        // Sack is required for images
        wp_print_scripts( array( 'sack', 'gform_gravityforms' ) );
    }


    /**
     * Process edit entry form save
     */
    private function process_save() {

        if( empty( $_POST ) || ! isset( $_POST['lid'] ) ) {
            return;
        }

        // Make sure the entry, view, and form IDs are all correct
        $valid = $this->verify_nonce();

        if( !$valid ) {
            do_action('gravityview_log_error', __METHOD__ . ' Nonce validation failed.' );
            return;
        }

        if( $this->entry['id'] !== $_POST['lid'] ) {
            do_action('gravityview_log_error', __METHOD__ . ' Entry ID did not match posted entry ID.' );
            return;
        }

        do_action('gravityview_log_debug', __METHOD__ . ': $_POSTed data (sanitized): ', esc_html( print_r( $_POST, true ) ) );

        $this->process_save_process_files( $this->form_id );

        $this->validate();

        if( $this->is_valid ) {

            do_action('gravityview_log_debug', __METHOD__ . ': Submission is valid.' );

            /**
             * @hack This step is needed to unset the adminOnly from form fields, to add the calculation fields
             */
            $form = $this->form_prepare_for_save();

            /**
             * @hack to avoid the capability validation of the method save_lead for GF 1.9+
             */
            unset( $_GET['page'] );

            $date_created = $this->entry['date_created'];

            /**
             * @hack to force Gravity Forms to use $read_value_from_post in GFFormsModel::save_lead()
             * @since 1.17.2
             */
            unset( $this->entry['date_created'] );

            GFFormsModel::save_lead( $form, $this->entry );

	        // Delete the values for hidden inputs
	        $this->unset_hidden_field_values();
            
            $this->entry['date_created'] = $date_created;

            // Process calculation fields
            $this->update_calculation_fields();

            // Perform actions normally performed after updating a lead
            $this->after_update();

	        /**
             * Must be AFTER after_update()!
             * @see https://github.com/gravityview/GravityView/issues/764
             */
            $this->maybe_update_post_fields( $form );

            /**
             * @action `gravityview/edit_entry/after_update` Perform an action after the entry has been updated using Edit Entry
             * @param array $form Gravity Forms form array
             * @param string $entry_id Numeric ID of the entry that was updated
             * @param GravityView_Edit_Entry_Render $this This object
             */
            do_action( 'gravityview/edit_entry/after_update', $this->form, $this->entry['id'], $this );

        } else {
            do_action('gravityview_log_error', __METHOD__ . ': Submission is NOT valid.', $this->entry );
        }

    } // process_save

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

		if ( method_exists( 'GFFormsModel', 'get_entry_meta_table_name' ) ) {
			$entry_meta_table = GFFormsModel::get_entry_meta_table_name();
			$current_fields = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $entry_meta_table WHERE entry_id=%d", $this->entry['id'] ) );
		} else {
			$lead_detail_table = GFFormsModel::get_lead_details_table_name();
			$current_fields = $wpdb->get_results( $wpdb->prepare( "SELECT id, field_number FROM $lead_detail_table WHERE lead_id=%d", $this->entry['id'] ) );
		}

	    foreach ( $this->entry as $input_id => $field_value ) {

		    $field = RGFormsModel::get_field( $this->form, $input_id );

		    // Reset fields that are hidden
		    // Don't pass $entry as fourth parameter; force using $_POST values to calculate conditional logic
		    if ( GFFormsModel::is_field_hidden( $this->form, $field, array(), NULL ) ) {

		        // List fields are stored as empty arrays when empty
			    $empty_value = $this->is_field_json_encoded( $field ) ? '[]' : '';

			    $lead_detail_id = GFFormsModel::get_lead_detail_id( $current_fields, $input_id );

			    GFFormsModel::update_lead_field_value( $this->form, $this->entry, $field, $lead_detail_id, $input_id, $empty_value );

			    // Prevent the $_POST values of hidden fields from being used as default values when rendering the form
                // after submission
			    $post_input_id = 'input_' . str_replace( '.', '_', $input_id );
			    $_POST[ $post_input_id ] = '';
		    }
	    }
    }

    /**
     * Have GF handle file uploads
     *
     * Copy of code from GFFormDisplay::process_form()
     *
     * @param int $form_id
     */
    private function process_save_process_files( $form_id ) {

        //Loading files that have been uploaded to temp folder
        $files = GFCommon::json_decode( stripslashes( RGForms::post( 'gform_uploaded_files' ) ) );
        if ( ! is_array( $files ) ) {
            $files = array();
        }

		/**
		 * Make sure the fileuploads are not overwritten if no such request was done.
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
	 * @param string $value Field value
	 * @param array $entry GF entry array
	 * @param GF_Field_FileUpload $field
	 * @param array $form GF form array
	 * @param string $input_id ID of the input being saved
	 *
	 * @return string
	 */
	public function save_field_value( $value = '', $entry = array(), $field = null, $form = array(), $input_id = '' ) {

		if ( ! $field || $field->type != 'fileupload' ) {
			return $value;
		}

		$input_name = 'input_' . str_replace( '.', '_', $input_id );

		if ( $field->multipleFiles ) {
			if ( empty( $value ) ) {
				return json_decode( $entry[ $input_id ], true );
			}
			return $value;
		}

		/** No file is being uploaded. */
		if ( empty( $_FILES[ $input_name ]['name'] ) ) {
			/** So return the original upload */
			return $entry[ $input_id ];
		}

		return $value;
	}

    /**
     * Remove max_files validation (done on gravityforms.js) to avoid conflicts with GravityView
     * Late validation done on self::custom_validation
     *
     * @param $plupload_init array Plupload settings
     * @param $form_id
     * @param $instance
     * @return mixed
     */
    public function modify_fileupload_settings( $plupload_init, $form_id, $instance ) {
        if( ! $this->is_edit_entry() ) {
            return $plupload_init;
        }

        $plupload_init['gf_vars']['max_files'] = 0;

        return $plupload_init;
    }


    /**
     * Unset adminOnly and convert field input key to string
     * @return array $form
     */
    private function form_prepare_for_save() {

        $form = $this->form;

	    /** @var GF_Field $field */
        foreach( $form['fields'] as $k => &$field ) {

            /**
             * Remove the fields with calculation formulas before save to avoid conflicts with GF logic
             * @since 1.16.3
             * @var GF_Field $field
             */
            if( $field->has_calculation() ) {
                unset( $form['fields'][ $k ] );
            }

            $field->adminOnly = false;

            if( isset( $field->inputs ) && is_array( $field->inputs ) ) {
                foreach( $field->inputs as $key => $input ) {
                    $field->inputs[ $key ][ 'id' ] = (string)$input['id'];
                }
            }
        }

        return $form;
    }

    private function update_calculation_fields() {

        $form = self::$original_form;
        $update = false;

        // get the most up to date entry values
        $entry = GFAPI::get_entry( $this->entry['id'] );

        if( !empty( $this->fields_with_calculation ) ) {
            $update = true;
            foreach ( $this->fields_with_calculation as $calc_field ) {
                $inputs = $calc_field->get_entry_inputs();
                if ( is_array( $inputs ) ) {
                    foreach ( $inputs as $input ) {
                        $input_name = 'input_' . str_replace( '.', '_', $input['id'] );
                        $entry[ strval( $input['id'] ) ] = RGFormsModel::prepare_value( $form, $calc_field, '', $input_name, $entry['id'], $entry );
                    }
                } else {
                    $input_name = 'input_' . str_replace( '.', '_', $calc_field->id);
                    $entry[ strval( $calc_field->id ) ] = RGFormsModel::prepare_value( $form, $calc_field, '', $input_name, $entry['id'], $entry );
                }
            }

        }

        if( $update ) {

            $return_entry = GFAPI::update_entry( $entry );

            if( is_wp_error( $return_entry ) ) {
                do_action( 'gravityview_log_error', 'Updating the entry calculation fields failed', $return_entry );
            } else {
                do_action( 'gravityview_log_debug', 'Updating the entry calculation fields succeeded' );
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
     * @param array $form GF Form array
     * @param GF_Field $field GF Field
     * @param string $field_id Numeric ID of the field
     * @param string $value
     * @param array $entry GF Entry currently being edited
     * @param int $post_id ID of the Post being edited
     *
     * @return mixed|string
     */
    private function update_post_image( $form, $field, $field_id, $value, $entry, $post_id ) {

        $input_name = 'input_' . $field_id;

        if ( !empty( $_FILES[ $input_name ]['name'] ) ) {

            // We have a new image

            $value = RGFormsModel::prepare_value( $form, $field, $value, $input_name, $entry['id'] );

            $ary = ! empty( $value ) ? explode( '|:|', $value ) : array();
            $img_url = rgar( $ary, 0 );

            $img_title       = count( $ary ) > 1 ? $ary[1] : '';
            $img_caption     = count( $ary ) > 2 ? $ary[2] : '';
            $img_description = count( $ary ) > 3 ? $ary[3] : '';

            $image_meta = array(
                'post_excerpt' => $img_caption,
                'post_content' => $img_description,
            );

            //adding title only if it is not empty. It will default to the file name if it is not in the array
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

        } elseif ( !empty( $_POST[ $input_name ] ) && is_array( $value ) ) {

            $img_url = $_POST[ $input_name ];

			$img_title       = rgar( $_POST, $input_name.'_1' );
			$img_caption     = rgar( $_POST, $input_name .'_4' );
			$img_description = rgar( $_POST, $input_name .'_7' );

			$value = ! empty( $img_url ) ? $img_url . "|:|" . $img_title . "|:|" . $img_caption . "|:|" . $img_description : '';

			if ( $field->postFeaturedImage ) {

				$image_meta = array(
					'ID' => get_post_thumbnail_id( $post_id ),
					'post_title' => $img_title,
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

        if( empty( $this->entry['post_id'] ) ) {
	        do_action( 'gravityview_log_debug', __METHOD__ . ': This entry has no post fields. Continuing...' );
            return;
        }

        $post_id = $this->entry['post_id'];

        // Security check
        if( false === GVCommon::has_cap( 'edit_post', $post_id ) ) {
            do_action( 'gravityview_log_error', 'The current user does not have the ability to edit Post #'.$post_id );
            return;
        }

        $update_entry = false;

        $updated_post = $original_post = get_post( $post_id );

        foreach ( $this->entry as $field_id => $value ) {

            $field = RGFormsModel::get_field( $form, $field_id );

            if( ! $field ) {
                continue;
            }

            if( GFCommon::is_post_field( $field ) && 'post_category' !== $field->type ) {

                // Get the value of the field, including $_POSTed value
                $value = RGFormsModel::get_field_value( $field );

                // Use temporary entry variable, to make values available to fill_post_template() and update_post_image()
                $entry_tmp = $this->entry;
                $entry_tmp["{$field_id}"] = $value;

                switch( $field->type ) {

                    case 'post_title':
                        $post_title = $value;
                        if( rgar( $form, 'postTitleTemplateEnabled' ) ) {
                            $post_title = $this->fill_post_template( $form['postTitleTemplate'], $form, $entry_tmp );
                        }
                        $updated_post->post_title = $post_title;
                        $updated_post->post_name  = $post_title;
                        unset( $post_title );
                        break;

                    case 'post_content':
                        $post_content = $value;
                        if( rgar( $form, 'postContentTemplateEnabled' ) ) {
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
						if ( is_array( $value ) && ( floatval( $field_id ) !== floatval( $field->id ) ) ) {
							$value = $value[ $field_id ];
						}

                        if( ! empty( $field->customFieldTemplateEnabled ) ) {
                            $value = $this->fill_post_template( $field->customFieldTemplate, $form, $entry_tmp, true );
                        }

	                    if ( $this->is_field_json_encoded( $field ) && ! is_string( $value ) ) {
		                    $value = function_exists('wp_json_encode') ? wp_json_encode( $value ) : json_encode( $value );
	                    }

                        update_post_meta( $post_id, $field->postCustomFieldName, $value );
                        break;

                    case 'post_image':
                        $value = $this->update_post_image( $form, $field, $field_id, $value, $this->entry, $post_id );
                        break;

                }

                // update entry after
                $this->entry["{$field_id}"] = $value;

                $update_entry = true;

                unset( $entry_tmp );
            }

        }

        if( $update_entry ) {

            $return_entry = GFAPI::update_entry( $this->entry );

            if( is_wp_error( $return_entry ) ) {
               do_action( 'gravityview_log_error', 'Updating the entry post fields failed', array( '$this->entry' => $this->entry, '$return_entry' => $return_entry ) );
            } else {
                do_action( 'gravityview_log_debug', 'Updating the entry post fields for post #'.$post_id.' succeeded' );
            }

        }

        $return_post = wp_update_post( $updated_post, true );

        if( is_wp_error( $return_post ) ) {
            $return_post->add_data( $updated_post, '$updated_post' );
            do_action( 'gravityview_log_error', 'Updating the post content failed', compact( 'updated_post', 'return_post' ) );
        } else {
            do_action( 'gravityview_log_debug', 'Updating the post content for post #'.$post_id.' succeeded', $updated_post );
        }
    }

	/**
     * Is the field stored in a JSON-encoded manner?
     *
	 * @param GF_Field $field
	 *
	 * @return bool True: stored in DB json_encode()'d; False: not encoded
	 */
    private function is_field_json_encoded( $field ) {

	    $json_encoded = false;

        $input_type = RGFormsModel::get_input_type( $field );

	    // Only certain custom field types are supported
	    switch( $input_type ) {
		    case 'fileupload':
		    case 'list':
		    case 'multiselect':
			    $json_encoded = true;
			    break;
	    }

	    return $json_encoded;
    }

    /**
     * Convert a field content template into prepared output
     *
     * @uses GravityView_GFFormsModel::get_post_field_images()
     *
     * @since 1.17
     *
     * @param string $template The content template for the field
     * @param array $form Gravity Forms form
     * @param bool $do_shortcode Whether to process shortcode inside content. In GF, only run on Custom Field and Post Content fields
     *
     * @return string
     */
    private function fill_post_template( $template, $form, $entry, $do_shortcode = false ) {

        require_once GRAVITYVIEW_DIR . 'includes/class-gravityview-gfformsmodel.php';

        $post_images = GravityView_GFFormsModel::get_post_field_images( $form, $entry );

        //replacing post image variables
        $output = GFCommon::replace_variables_post_image( $template, $post_images, $entry );

        //replacing all other variables
        $output = GFCommon::replace_variables( $output, $form, $entry, false, false, false );

        // replace conditional shortcodes
        if( $do_shortcode ) {
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

        do_action( 'gform_after_update_entry', $this->form, $this->entry['id'], self::$original_entry );
        do_action( "gform_after_update_entry_{$this->form['id']}", $this->form, $this->entry['id'], self::$original_entry );

        // Re-define the entry now that we've updated it.
        $entry = RGFormsModel::get_lead( $this->entry['id'] );

        $entry = GFFormsModel::set_entry_meta( $entry, $this->form );

		if ( ! method_exists( 'GFFormsModel', 'get_entry_meta_table_name' ) ) {
			// We need to clear the cache because Gravity Forms caches the field values, which
			// we have just updated.
			foreach ($this->form['fields'] as $key => $field) {
				GFFormsModel::refresh_lead_field_value( $entry['id'], $field->id );
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

        ?>

        <div class="gv-edit-entry-wrapper"><?php

            $javascript = gravityview_ob_include( GravityView_Edit_Entry::$file .'/partials/inline-javascript.php', $this );

            /**
             * Fixes weird wpautop() issue
             * @see https://github.com/katzwebservices/GravityView/issues/451
             */
            echo gravityview_strip_whitespace( $javascript );

            ?><h2 class="gv-edit-entry-title">
                <span><?php

                    /**
                     * @filter `gravityview_edit_entry_title` Modify the edit entry title
                     * @param string $edit_entry_title Modify the "Edit Entry" title
                     * @param GravityView_Edit_Entry_Render $this This object
                     */
                    $edit_entry_title = apply_filters('gravityview_edit_entry_title', __('Edit Entry', 'gravityview'), $this );

                    echo esc_attr( $edit_entry_title );
            ?></span>
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

            <script>
                gform.addFilter('gform_reset_pre_conditional_logic_field_action', function ( reset, formId, targetId, defaultValues, isInit ) {
                    return false;
                });
            </script>

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

        if( rgpost('action') === 'update' ) {

            $back_link = esc_url( remove_query_arg( array( 'page', 'view', 'edit' ) ) );

            if( ! $this->is_valid ){

                // Keeping this compatible with Gravity Forms.
                $validation_message = "<div class='validation_error'>" . __('There was a problem with your submission.', 'gravityview') . " " . __('Errors have been highlighted below.', 'gravityview') . "</div>";
                $message = apply_filters("gform_validation_message_{$this->form['id']}", apply_filters("gform_validation_message", $validation_message, $this->form), $this->form);

                echo GVCommon::generate_notice( $message , 'gv-error' );

            } else {
                $entry_updated_message = sprintf( esc_attr__('Entry Updated. %sReturn to Entry%s', 'gravityview'), '<a href="'. $back_link .'">', '</a>' );

                /**
                 * @filter `gravityview/edit_entry/success` Modify the edit entry success message (including the anchor link)
                 * @since 1.5.4
                 * @param string $entry_updated_message Existing message
                 * @param int $view_id View ID
                 * @param array $entry Gravity Forms entry array
                 * @param string $back_link URL to return to the original entry. @since 1.6
                 */
                $message = apply_filters( 'gravityview/edit_entry/success', $entry_updated_message , $this->view_id, $this->entry, $back_link );

                echo GVCommon::generate_notice( $message );
            }

        }
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
         * @action `gravityview/edit-entry/render/before` Before rendering the Edit Entry form
         * @since 1.17
         * @param GravityView_Edit_Entry_Render $this
         */
        do_action( 'gravityview/edit-entry/render/before', $this );

        add_filter( 'gform_pre_render', array( $this, 'filter_modify_form_fields'), 5000, 3 );
        add_filter( 'gform_submit_button', array( $this, 'render_form_buttons') );
        add_filter( 'gform_disable_view_counter', '__return_true' );

        add_filter( 'gform_field_input', array( $this, 'verify_user_can_edit_post' ), 5, 5 );
        add_filter( 'gform_field_input', array( $this, 'modify_edit_field_input' ), 10, 5 );

        // We need to remove the fake $_GET['page'] arg to avoid rendering form as if in admin.
        unset( $_GET['page'] );

        // TODO: Verify multiple-page forms

        ob_start(); // Prevent PHP warnings possibly caused by prefilling list fields for conditional logic

        $html = GFFormDisplay::get_form( $this->form['id'], false, false, true, $this->entry );

        ob_get_clean();

	    remove_filter( 'gform_pre_render', array( $this, 'filter_modify_form_fields' ), 5000 );
        remove_filter( 'gform_submit_button', array( $this, 'render_form_buttons' ) );
        remove_filter( 'gform_disable_view_counter', '__return_true' );
        remove_filter( 'gform_field_input', array( $this, 'verify_user_can_edit_post' ), 5 );
        remove_filter( 'gform_field_input', array( $this, 'modify_edit_field_input' ), 10 );

        echo $html;

        /**
         * @action `gravityview/edit-entry/render/after` After rendering the Edit Entry form
         * @since 1.17
         * @param GravityView_Edit_Entry_Render $this
         */
        do_action( 'gravityview/edit-entry/render/after', $this );
    }

    /**
     * Display the Update/Cancel/Delete buttons for the Edit Entry form
     * @since 1.8
     * @return string
     */
    public function render_form_buttons() {
        return gravityview_ob_include( GravityView_Edit_Entry::$file .'/partials/form-buttons.php', $this );
    }


    /**
     * Modify the form fields that are shown when using GFFormDisplay::get_form()
     *
     * By default, all fields will be shown. We only want the Edit Tab configured fields to be shown.
     *
     * @param array $form
     * @param boolean $ajax Whether in AJAX mode
     * @param array|string $field_values Passed parameters to the form
     *
     * @since 1.9
     *
     * @return array Modified form array
     */
    public function filter_modify_form_fields( $form, $ajax = false, $field_values = '' ) {

        // In case we have validated the form, use it to inject the validation results into the form render
        if( isset( $this->form_after_validation ) ) {
            $form = $this->form_after_validation;
        } else {
            $form['fields'] = $this->get_configured_edit_fields( $form, $this->view_id );
        }

        $form = $this->filter_conditional_logic( $form );

        $form = $this->prefill_conditional_logic( $form );

        // for now we don't support Save and Continue feature.
        if( ! self::$supports_save_and_continue ) {
	        unset( $form['save'] );
        }

        return $form;
    }

    /**
     * When displaying a field, check if it's a Post Field, and if so, make sure the post exists and current user has edit rights.
     *
     * @since 1.16.2.2
     *
     * @param string $field_content Always empty. Returning not-empty overrides the input.
     * @param GF_Field $field
     * @param string|array $value If array, it's a field with multiple inputs. If string, single input.
     * @param int $lead_id Lead ID. Always 0 for the `gform_field_input` filter.
     * @param int $form_id Form ID
     *
     * @return string If error, the error message. If no error, blank string (modify_edit_field_input() runs next)
     */
    public function verify_user_can_edit_post( $field_content = '', $field, $value, $lead_id = 0, $form_id ) {

        if( GFCommon::is_post_field( $field ) ) {

            $message = null;

            // First, make sure they have the capability to edit the post.
            if( false === current_user_can( 'edit_post', $this->entry['post_id'] ) ) {

                /**
                 * @filter `gravityview/edit_entry/unsupported_post_field_text` Modify the message when someone isn't able to edit a post
                 * @param string $message The existing "You don't have permission..." text
                 */
                $message = apply_filters('gravityview/edit_entry/unsupported_post_field_text', __('You don&rsquo;t have permission to edit this post.', 'gravityview') );

            } elseif( null === get_post( $this->entry['post_id'] ) ) {
                /**
                 * @filter `gravityview/edit_entry/no_post_text` Modify the message when someone is editing an entry attached to a post that no longer exists
                 * @param string $message The existing "This field is not editable; the post no longer exists." text
                 */
                $message = apply_filters('gravityview/edit_entry/no_post_text', __('This field is not editable; the post no longer exists.', 'gravityview' ) );
            }

            if( $message ) {
                $field_content = sprintf('<div class="ginput_container ginput_container_' . $field->type . '">%s</div>', wpautop( $message ) );
            }
        }

        return $field_content;
    }

    /**
     *
     * Fill-in the saved values into the form inputs
     *
     * @param string $field_content Always empty. Returning not-empty overrides the input.
     * @param GF_Field $field
     * @param string|array $value If array, it's a field with multiple inputs. If string, single input.
     * @param int $lead_id Lead ID. Always 0 for the `gform_field_input` filter.
     * @param int $form_id Form ID
     *
     * @return mixed
     */
    public function modify_edit_field_input( $field_content = '', $field, $value, $lead_id = 0, $form_id ) {

        $gv_field = GravityView_Fields::get_associated_field( $field );

        // If the form has been submitted, then we don't need to pre-fill the values,
        // Except for fileupload type and when a field input is overridden- run always!!
        if(
            ( $this->is_edit_entry_submission() && !in_array( $field->type, array( 'fileupload', 'post_image' ) ) )
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
        if( $gv_field && is_callable( array( $gv_field, 'get_field_input' ) ) ) {
            $return = $gv_field->get_field_input( $this->form, $field_value, $this->entry, $field );
        } else {
	        $return = $field->get_field_input( $this->form, $field_value, $this->entry );
	    }

	    // If there was output, it's an error
	    $warnings = ob_get_clean();

	    if( !empty( $warnings ) ) {
		    do_action( 'gravityview_log_error', __METHOD__ . $warnings, $field_value );
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
         * @filter `gravityview/edit_entry/pre_populate/override` Allow the pre-populated value to override saved value in Edit Entry form. By default, pre-populate mechanism only kicks on empty fields.
         * @param boolean True: override saved values; False: don't override (default)
         * @param $field GF_Field object Gravity Forms field object
         * @since 1.13
         */
        $override_saved_value = apply_filters( 'gravityview/edit_entry/pre_populate/override', false, $field );

        // We're dealing with multiple inputs (e.g. checkbox) but not time or date (as it doesn't store data in input IDs)
        if( isset( $field->inputs ) && is_array( $field->inputs ) && !in_array( $field->type, array( 'time', 'date' ) ) ) {

            $field_value = array();

            // only accept pre-populated values if the field doesn't have any choice selected.
            $allow_pre_populated = $field->allowsPrepopulate;

            foreach ( (array)$field->inputs as $input ) {

                $input_id = strval( $input['id'] );
                
                if ( isset( $this->entry[ $input_id ] ) && ! gv_empty( $this->entry[ $input_id ], false, false ) ) {
                    $field_value[ $input_id ] =  'post_category' === $field->type ? GFCommon::format_post_category( $this->entry[ $input_id ], true ) : $this->entry[ $input_id ];
                    $allow_pre_populated = false;
                }

            }

            $pre_value = $field->get_value_submission( array(), false );

            $field_value = ! $allow_pre_populated && ! ( $override_saved_value && !gv_empty( $pre_value, false, false ) ) ? $field_value : $pre_value;

        } else {

            $id = intval( $field->id );

            // get pre-populated value if exists
            $pre_value = $field->allowsPrepopulate ? GFFormsModel::get_parameter_value( $field->inputName, array(), $field ) : '';

            // saved field entry value (if empty, fallback to the pre-populated value, if exists)
            // or pre-populated value if not empty and set to override saved value
            $field_value = !gv_empty( $this->entry[ $id ], false, false ) && ! ( $override_saved_value && !gv_empty( $pre_value, false, false ) ) ? $this->entry[ $id ] : $pre_value;

            // in case field is post_category but inputType is select, multi-select or radio, convert value into array of category IDs.
            if ( 'post_category' === $field->type && !gv_empty( $field_value, false, false ) ) {
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
	     * @filter `gravityview/edit_entry/field_value` Change the value of an Edit Entry field, if needed
	     * @since 1.11
	     * @since 1.20 Added third param
	     * @param mixed $field_value field value used to populate the input
	     * @param object $field Gravity Forms field object ( Class GF_Field )
	     * @param GravityView_Edit_Entry_Render $this Current object
	     */
	    $field_value = apply_filters( 'gravityview/edit_entry/field_value', $field_value, $field, $this );

	    /**
	     * @filter `gravityview/edit_entry/field_value_{field_type}` Change the value of an Edit Entry field for a specific field type
	     * @since 1.17
	     * @since 1.20 Added third param
	     * @param mixed $field_value field value used to populate the input
	     * @param GF_Field $field Gravity Forms field object
	     * @param GravityView_Edit_Entry_Render $this Current object
	     */
	    $field_value = apply_filters( 'gravityview/edit_entry/field_value_' . $field->type , $field_value, $field, $this );

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

        if( ! $this->verify_nonce() ) {
            return $form;
        }

        // Fix PHP warning regarding undefined index.
        foreach ( $form['fields'] as &$field) {

            // This is because we're doing admin form pretending to be front-end, so Gravity Forms
            // expects certain field array items to be set.
            foreach ( array( 'noDuplicates', 'adminOnly', 'inputType', 'isRequired', 'enablePrice', 'inputs', 'allowedExtensions' ) as $key ) {
	            $field->{$key} = isset( $field->{$key} ) ? $field->{$key} : NULL;
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

                    // Set the previous value
                    $entry = $this->get_entry();

                    $input_name = 'input_'.$field->id;
                    $form_id = $form['id'];

                    $value = NULL;

                    // Use the previous entry value as the default.
                    if( isset( $entry[ $field->id ] ) ) {
                        $value = $entry[ $field->id ];
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

                    $this->entry[ $input_name ] = $value;
                    $_POST[ $input_name ] = $value;

                    break;

                case 'number':
                    // Fix "undefined index" issue at line 1286 in form_display.php
                    if( !isset( $_POST['input_'.$field->id ] ) ) {
                        $_POST['input_'.$field->id ] = NULL;
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
         * @since 1.16.2
         */
        if ( class_exists( 'GF_User_Registration' ) ) {
            remove_filter( 'gform_validation', array( GF_User_Registration::get_instance(), 'validate' ) );
        } else  if ( class_exists( 'GFUser' ) ) {
            remove_filter( 'gform_validation', array( 'GFUser', 'user_registration_validation' ) );
        }


        /**
         * For some crazy reason, Gravity Forms doesn't validate Edit Entry form submissions.
         * You can enter whatever you want!
         * We try validating, and customize the results using `self::custom_validation()`
         */
        add_filter( 'gform_validation_'. $this->form_id, array( $this, 'custom_validation' ), 10, 4);

        // Needed by the validate funtion
        $failed_validation_page = NULL;
        $field_values = RGForms::post( 'gform_field_values' );

        // Prevent entry limit from running when editing an entry, also
        // prevent form scheduling from preventing editing
        unset( $this->form['limitEntries'], $this->form['scheduleForm'] );

        // Hide fields depending on Edit Entry settings
        $this->form['fields'] = $this->get_configured_edit_fields( $this->form, $this->view_id );

        $this->is_valid = GFFormDisplay::validate( $this->form, $field_values, 1, $failed_validation_page );

        remove_filter( 'gform_validation_'. $this->form_id, array( $this, 'custom_validation' ), 10 );
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
    public function custom_validation( $validation_results ) {

        do_action('gravityview_log_debug', 'GravityView_Edit_Entry[custom_validation] Validation results: ', $validation_results );

        do_action('gravityview_log_debug', 'GravityView_Edit_Entry[custom_validation] $_POSTed data (sanitized): ', esc_html( print_r( $_POST, true ) ) );

        $gv_valid = true;

        foreach ( $validation_results['form']['fields'] as $key => &$field ) {

            $value = RGFormsModel::get_field_value( $field );
            $field_type = RGFormsModel::get_input_type( $field );

            // Validate always
            switch ( $field_type ) {


                case 'fileupload' :
                case 'post_image':

                    // in case nothing is uploaded but there are already files saved
                    if( !empty( $field->failed_validation ) && !empty( $field->isRequired ) && !empty( $value ) ) {
                        $field->failed_validation = false;
                        unset( $field->validation_message );
                    }

                    // validate if multi file upload reached max number of files [maxFiles] => 2
                    if( rgobj( $field, 'maxFiles') && rgobj( $field, 'multipleFiles') ) {

                        $input_name = 'input_' . $field->id;
                        //uploaded
                        $file_names = isset( GFFormsModel::$uploaded_files[ $validation_results['form']['id'] ][ $input_name ] ) ? GFFormsModel::$uploaded_files[ $validation_results['form']['id'] ][ $input_name ] : array();

                        //existent
                        $entry = $this->get_entry();
                        $value = NULL;
                        if( isset( $entry[ $field->id ] ) ) {
                            $value = json_decode( $entry[ $field->id ], true );
                        }

                        // count uploaded files and existent entry files
                        $count_files = count( $file_names ) + count( $value );

                        if( $count_files > $field->maxFiles ) {
                            $field->validation_message = __( 'Maximum number of files reached', 'gravityview' );
                            $field->failed_validation = 1;
                            $gv_valid = false;

                            // in case of error make sure the newest upload files are removed from the upload input
                            GFFormsModel::$uploaded_files[ $validation_results['form']['id'] ] = null;
                        }

                    }


                    break;

            }

            // This field has failed validation.
            if( !empty( $field->failed_validation ) ) {

                do_action( 'gravityview_log_debug', 'GravityView_Edit_Entry[custom_validation] Field is invalid.', array( 'field' => $field, 'value' => $value ) );

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
                if( empty( $field->failed_validation ) ) {
                    continue;
                }

                // checks if the No Duplicates option is not validating entry against itself, since
                // we're editing a stored entry, it would also assume it's a duplicate.
                if( !empty( $field->noDuplicates ) ) {

                    $entry = $this->get_entry();

                    // If the value of the entry is the same as the stored value
                    // Then we can assume it's not a duplicate, it's the same.
                    if( !empty( $entry ) && $value == $entry[ $field->id ] ) {
                        //if value submitted was not changed, then don't validate
                        $field->failed_validation = false;

                        unset( $field->validation_message );

                        do_action('gravityview_log_debug', 'GravityView_Edit_Entry[custom_validation] Field not a duplicate; it is the same entry.', $entry );

                        continue;
                    }
                }

                // if here then probably we are facing the validation 'At least one field must be filled out'
                if( GFFormDisplay::is_empty( $field, $this->form_id  ) && empty( $field->isRequired ) ) {
                    unset( $field->validation_message );
	                $field->validation_message = false;
                    continue;
                }

                $gv_valid = false;

            }

        }

        $validation_results['is_valid'] = $gv_valid;

        do_action('gravityview_log_debug', 'GravityView_Edit_Entry[custom_validation] Validation results.', $validation_results );

        // We'll need this result when rendering the form ( on GFFormDisplay::get_form )
        $this->form_after_validation = $validation_results['form'];

        return $validation_results;
    }


    /**
     * TODO: This seems to be hacky... we should remove it. Entry is set when updating the form using setup_vars()!
     * Get the current entry and set it if it's not yet set.
     * @return array Gravity Forms entry array
     */
    public function get_entry() {

        if( empty( $this->entry ) ) {
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
		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			if ( \GV\View::exists( $view_id ) ) {
				$view = \GV\View::by_id( $view_id );
				$properties = $view->fields->as_configuration();
			}
		} else {
			/** GravityView_View_Data is deprecated. */
			$properties = GravityView_View_Data::getInstance()->get_fields( $view_id );
		}

        // If edit tab not yet configured, show all fields
        $edit_fields = !empty( $properties['edit_edit-fields'] ) ? $properties['edit_edit-fields'] : NULL;

        // Hide fields depending on admin settings
        $fields = $this->filter_fields( $form['fields'], $edit_fields );

	    // If Edit Entry fields are configured, remove adminOnly field settings. Otherwise, don't.
	    $fields = $this->filter_admin_only_fields( $fields, $edit_fields, $form, $view_id );

        /**
         * @filter `gravityview/edit_entry/form_fields` Modify the fields displayed in Edit Entry form
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
     *
     * @uses GravityView_Edit_Entry::user_can_edit_field() Check caps
     * @access private
     * @param GF_Field[] $fields
     * @param array $configured_fields
     * @since  1.5
     * @return array $fields
     */
    private function filter_fields( $fields, $configured_fields ) {

        if( empty( $fields ) || !is_array( $fields ) ) {
            return $fields;
        }

        $edit_fields = array();

        $field_type_blacklist = $this->loader->get_field_blacklist( $this->entry );

        // First, remove blacklist or calculation fields
        foreach ( $fields as $key => $field ) {

            // Remove the fields that have calculation properties and keep them to be used later
            // @since 1.16.2
            if( $field->has_calculation() ) {
                $this->fields_with_calculation[] = $field;
                // don't remove the calculation fields on form render.
            }

            if( in_array( $field->type, $field_type_blacklist ) ) {
                unset( $fields[ $key ] );
            }
        }

        // The Edit tab has not been configured, so we return all fields by default.
        if( empty( $configured_fields ) ) {
            return $fields;
        }

        // The edit tab has been configured, so we loop through to configured settings
        foreach ( $configured_fields as $configured_field ) {

	        /** @var GF_Field $field */
	        foreach ( $fields as $field ) {

                if( intval( $configured_field['id'] ) === intval( $field->id ) && $this->user_can_edit_field( $configured_field, false ) ) {
                    $edit_fields[] = $this->merge_field_properties( $field, $configured_field );
                    break;
                }

            }

        }

        return $edit_fields;

    }

    /**
     * Override GF Form field properties with the ones defined on the View
     * @param  GF_Field $field GF Form field object
     * @param  array $field_setting  GV field options
     * @since  1.5
     * @return array|GF_Field
     */
    private function merge_field_properties( $field, $field_setting ) {

        $return_field = $field;

        if( empty( $field_setting['show_label'] ) ) {
            $return_field->label = '';
        } elseif ( !empty( $field_setting['custom_label'] ) ) {
            $return_field->label = $field_setting['custom_label'];
        }

        if( !empty( $field_setting['custom_class'] ) ) {
            $return_field->cssClass .= ' '. gravityview_sanitize_html_class( $field_setting['custom_class'] );
        }

        /**
         * Normalize page numbers - avoid conflicts with page validation
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
     * @param array|null $edit_fields Fields for the Edit Entry tab configured in the View Configuration
     * @param array $form GF Form array
     * @param int $view_id View ID
     *
     * @return array Possibly modified form array
     */
    private function filter_admin_only_fields( $fields = array(), $edit_fields = null, $form = array(), $view_id = 0 ) {

	    /**
         * @filter `gravityview/edit_entry/use_gf_admin_only_setting` When Edit tab isn't configured, should the Gravity Forms "Admin Only" field settings be used to control field display to non-admins? Default: true
	     * If the Edit Entry tab is not configured, adminOnly fields will not be shown to non-administrators.
	     * If the Edit Entry tab *is* configured, adminOnly fields will be shown to non-administrators, using the configured GV permissions
	     * @since 1.9.1
	     * @param boolean $use_gf_adminonly_setting True: Hide field if set to Admin Only in GF and the user is not an admin. False: show field based on GV permissions, ignoring GF permissions.
	     * @param array $form GF Form array
	     * @param int $view_id View ID
	     */
	    $use_gf_adminonly_setting = apply_filters( 'gravityview/edit_entry/use_gf_admin_only_setting', empty( $edit_fields ), $form, $view_id );

	    if( $use_gf_adminonly_setting && false === GVCommon::has_cap( 'gravityforms_edit_entries', $this->entry['id'] ) ) {
            foreach( $fields as $k => $field ) {
                if( $field->adminOnly ) {
                    unset( $fields[ $k ] );
                }
            }
            return $fields;
        }

	    foreach( $fields as &$field ) {
		    $field->adminOnly = false;
        }

        return $fields;
    }

    // --- Conditional Logic

    /**
     * Conditional logic isn't designed to work with forms that already have content. When switching input values,
     * the dependent fields will be blank.
     *
     * Note: This is because GF populates a JavaScript variable with the input values. This is tough to filter at the input level;
     * via the `gform_field_value` filter; it requires lots of legwork. Doing it at the form level is easier.
     *
     * @since 1.17.4
     *
     * @param array $form Gravity Forms array object
     *
     * @return array $form, modified to fix conditional
     */
    function prefill_conditional_logic( $form ) {

        if( ! GFFormDisplay::has_conditional_logic( $form ) ) {
            return $form;
        }

        // Have Conditional Logic pre-fill fields as if the data were default values
        /** @var GF_Field $field */
        foreach ( $form['fields'] as &$field ) {

            if( 'checkbox' === $field->type ) {
                foreach ( $field->get_entry_inputs() as $key => $input ) {
                    $input_id = $input['id'];
                    $choice = $field->choices[ $key ];
                    $value = rgar( $this->entry, $input_id );
                    $match = RGFormsModel::choice_value_match( $field, $choice, $value );
                    if( $match ) {
                        $field->choices[ $key ]['isSelected'] = true;
                    }
                }
            } else {

                // We need to run through each field to set the default values
                foreach ( $this->entry as $field_id => $field_value ) {

                    if( floatval( $field_id ) === floatval( $field->id ) ) {

                        if( 'list' === $field->type ) {
                            $list_rows = maybe_unserialize( $field_value );

                            $list_field_value = array();
                            foreach ( (array) $list_rows as $row ) {
                                foreach ( (array) $row as $column ) {
                                    $list_field_value[] = $column;
                                }
                            }

                            $field->defaultValue = serialize( $list_field_value );
                        } else {
                            $field->defaultValue = $field_value;
                        }
                    }
                }
            }
        }

        return $form;
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
         * @filter `gravityview/edit_entry/conditional_logic` Should the Edit Entry form use Gravity Forms conditional logic showing/hiding of fields?
         * @since 1.9
         * @param bool $use_conditional_logic True: Gravity Forms will show/hide fields just like in the original form; False: conditional logic will be disabled and fields will be shown based on configuration. Default: true
         * @param array $form Gravity Forms form
         */
        $use_conditional_logic = apply_filters( 'gravityview/edit_entry/conditional_logic', true, $form );

        if( $use_conditional_logic ) {
            return $form;
        }

        foreach( $form['fields'] as &$field ) {
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

        if( ! $this->is_edit_entry() ) {
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

        if( ! GravityView_Edit_Entry::check_user_cap_edit_entry( $this->entry ) ) {
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

	        $error = esc_html( $error );

	        /**
	         * @since 1.9
	         */
	        if ( ! empty( $this->entry ) ) {
		        $error .= ' ' . gravityview_get_link( '#', _x('Go back.', 'Link shown when invalid Edit Entry link is clicked', 'gravityview' ), array( 'onclick' => "window.history.go(-1); return false;" ) );
	        }

            echo GVCommon::generate_notice( wpautop( $error ), 'gv-error error');
        }

        do_action('gravityview_log_error', 'GravityView_Edit_Entry[user_can_edit_entry]' . $error );

        return false;
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
            echo GVCommon::generate_notice( wpautop( esc_html( $error ) ), 'gv-error error');
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
        if( GVCommon::has_cap( array( 'gravityforms_edit_entries', 'gravityview_edit_others_entries' ) ) ) {
            return true;
        }

        $field_cap = isset( $field['allow_edit_cap'] ) ? $field['allow_edit_cap'] : false;

        // If the field has custom editing capaibilities set, check those
        if( $field_cap ) {
            return GVCommon::has_cap( $field['allow_edit_cap'] );
        }

        return false;
    }


    /**
     * Is the current nonce valid for editing the entry?
     * @return boolean
     */
    public function verify_nonce() {

        // Verify form submitted for editing single
        if( $this->is_edit_entry_submission() ) {
            $valid = wp_verify_nonce( $_POST[ self::$nonce_field ], self::$nonce_field );
        }

        // Verify
        else if( ! $this->is_edit_entry() ) {
            $valid = false;
        }

        else {
            $valid = wp_verify_nonce( $_GET['edit'], self::$nonce_key );
        }

        /**
         * @filter `gravityview/edit_entry/verify_nonce` Override Edit Entry nonce validation. Return true to declare nonce valid.
         * @since 1.13
         * @param int|boolean $valid False if invalid; 1 or 2 when nonce was generated
         * @param string $nonce_field Key used when validating submissions. Default: is_gv_edit_entry
         */
        $valid = apply_filters( 'gravityview/edit_entry/verify_nonce', $valid, self::$nonce_field );

        return $valid;
    }


	/**
	 * Multiselect in GF 2.2 became a json_encoded value. Fix it.
	 *
	 * As a hack for now we'll implode it back.
	 */
	public function fix_multiselect_value_serialization( $field_value, $field, $_this ) {
		if ( empty ( $field->storageType ) || $field->storageType != 'json' ) {
			return $field_value;
		}

		$maybe_json = @json_decode( $field_value, true );

		if ( $maybe_json ) {
			return implode( ',', $maybe_json );
		}

		return $field_value;
	}



} //end class
