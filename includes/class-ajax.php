<?php

class GravityView_Ajax {

	function __construct() {

		//get field options
		add_action( 'wp_ajax_gv_field_options', array( $this, 'get_field_options' ) );

		// get available fields
		add_action( 'wp_ajax_gv_available_fields', array( $this, 'get_available_fields_html' ) );

		// get active areas
		add_action( 'wp_ajax_gv_get_active_areas', array( $this, 'get_active_areas' ) );

		// get preset fields
		add_action( 'wp_ajax_gv_get_preset_fields', array( $this, 'get_preset_fields_config' ) );

		// get preset fields
		add_action( 'wp_ajax_gv_set_preset_form', array( $this, 'create_preset_form' ) );

		add_action( 'wp_ajax_gv_sortable_fields_form', array( $this, 'get_sortable_fields' ) );
	}

	/**
	 * Handle exiting the script (for unit testing)
	 *
	 * @since 1.15
	 * @param bool|false $mixed
	 *
	 * @return bool
	 */
	private function _exit( $mixed = NULL ) {

		/**
		 * Don't exit if we're running test suite.
		 * @since 1.15
		 */
		if( defined( 'DOING_GRAVITYVIEW_TESTS' ) && DOING_GRAVITYVIEW_TESTS ) {
			return $mixed;
		}

		exit( $mixed );
	}

	/** -------- AJAX ---------- */

	/**
	 * Verify the nonce. Exit if not verified.
	 * @return void
	 */
	function check_ajax_nonce() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxviews' ) ) {
			$this->_exit( false );
		}
	}

	/**
	 * Returns available fields given a form ID or a preset template ID
	 * AJAX callback
	 *
	 * @access public
	 * @return void
	 */
	function get_available_fields_html() {

		//check nonce
		$this->check_ajax_nonce();

		$context = isset($_POST['context']) ? esc_attr( $_POST['context'] ) : 'directory';

		// If Form was changed, JS sends form ID, if start fresh, JS sends template_id
		if( !empty( $_POST['form_id'] ) ) {
			do_action( 'gravityview_render_available_fields', (int) $_POST['form_id'], $context );
			$this->_exit();
		} elseif( !empty( $_POST['template_id'] ) ) {
			$form = GravityView_Ajax::pre_get_form_fields( $_POST['template_id'] );

			/** @see GravityView_Admin_Views::render_available_fields */
			do_action( 'gravityview_render_available_fields', $form, $context );
			$this->_exit();
		}

		//if everything fails..
		$this->_exit( false );
	}


	/**
	 * Returns template active areas given a template ID
	 * AJAX callback
	 *
	 * @access public
	 * @return void
	 */
	function get_active_areas() {
		$this->check_ajax_nonce();

		if( empty( $_POST['template_id'] ) ) {
			$this->_exit( false );
		}

		ob_start();
		do_action( 'gravityview_render_directory_active_areas', $_POST['template_id'], 'directory', '', true );
		$response['directory'] = ob_get_clean();

		ob_start();
		do_action( 'gravityview_render_directory_active_areas',  $_POST['template_id'], 'single', '', true );
		$response['single'] = ob_get_clean();

		$response = array_map( 'gravityview_strip_whitespace', $response );

		$this->_exit( json_encode( $response ) );
	}

	/**
	 * Fill in active areas with preset configuration according to the template selected
	 * @return void
	 */
	function get_preset_fields_config() {

		$this->check_ajax_nonce();

		if( empty( $_POST['template_id'] ) ) {
			$this->_exit( false );
		}

		// get the fields xml config file for this specific preset
		$preset_fields_path = apply_filters( 'gravityview_template_fieldsxml', array(), $_POST['template_id'] );
		// import fields
		if( !empty( $preset_fields_path ) ) {
			$presets = $this->import_fields( $preset_fields_path );
		} else {
			$presets = array( 'widgets' => array(), 'fields' => array() );
		}

		$template_id = esc_attr( $_POST['template_id'] );

		// template areas
		$template_areas_directory = apply_filters( 'gravityview_template_active_areas', array(), $template_id, 'directory' );
        $template_areas_single = apply_filters( 'gravityview_template_active_areas', array(), $template_id, 'single' );

		// widget areas
		$default_widget_areas = GravityView_Widget::get_default_widget_areas();

		ob_start();
		do_action('gravityview_render_active_areas', $template_id, 'widget', 'header', $default_widget_areas, $presets['widgets'] );
		$response['header'] = ob_get_clean();

		ob_start();
		do_action('gravityview_render_active_areas', $template_id, 'widget', 'footer', $default_widget_areas, $presets['widgets'] );
		$response['footer'] = ob_get_clean();

		ob_start();
		do_action('gravityview_render_active_areas', $template_id, 'field', 'directory', $template_areas_directory, $presets['fields'] );
		$response['directory'] = ob_get_clean();

		ob_start();
		do_action('gravityview_render_active_areas', $template_id, 'field', 'single', $template_areas_single, $presets['fields'] );
		$response['single'] = ob_get_clean();

		$response = array_map( 'gravityview_strip_whitespace', $response );

		gravityview()->log->debug( '[get_preset_fields_config] AJAX Response', array( 'data' => $response ) );

		$this->_exit( json_encode( $response ) );
	}

	/**
	 * Create the preset form requested before the View save
	 *
	 * @return void
	 */
	function create_preset_form() {

		$this->check_ajax_nonce();

		if( empty( $_POST['template_id'] ) ) {
			gravityview()->log->error( 'Cannot create preset form; the template_id is empty.' );
			$this->_exit( false );
		}

		// get the xml for this specific template_id
		$preset_form_xml_path = apply_filters( 'gravityview_template_formxml', '', $_POST['template_id'] );

		// import form
		$form = $this->import_form( $preset_form_xml_path );

		// get the form ID
		if( false === $form ) {
			// send error to user
			gravityview()->log->error( 'Error importing form for template id: {template_id}', array( 'template_id' => (int) $_POST['template_id'] ) );

			$this->_exit( false );
		}

		$this->_exit( '<option value="'.esc_attr( $form['id'] ).'" selected="selected">'.esc_html( $form['title'] ).'</option>' );

	}

	/**
	 * Import Gravity Form XML or JSON
	 *
	 * @param  string $xml_or_json_path Path to form XML or JSON file
	 * @return int|bool       Imported form ID or false
	 */
	function import_form( $xml_or_json_path = '' ) {

		gravityview()->log->debug( '[import_form] Import Preset Form. (File) {path}', array( 'path' => $xml_or_json_path ) );

		if( empty( $xml_or_json_path ) || !class_exists('GFExport') || !file_exists( $xml_or_json_path ) ) {
			gravityview()->log->error( 'Class GFExport or file not found. file: {path}', array( 'path' => $xml_or_json_path ) );
			return false;
		}

		// import form
		$forms = '';
		$count = GFExport::import_file( $xml_or_json_path, $forms );

		gravityview()->log->debug( '[import_form] Importing form (Result) {count}', array( 'count' => $count ) );
		gravityview()->log->debug( '[import_form] Importing form (Form) ', array( 'data' => $forms ) );

		if( $count != 1 || empty( $forms[0]['id'] ) ) {
			gravityview()->log->error( 'Form Import Failed!' );
			return false;
		}

		// import success - return form id
		return $forms[0];
	}


	/**
	 * Returns field options - called by ajax when dropping fields into active areas
	 * AJAX callback
	 *
	 * @access public
	 * @return void
	 */
	function get_field_options() {
		$this->check_ajax_nonce();
		
		if( empty( $_POST['template'] ) || empty( $_POST['area'] ) || empty( $_POST['field_id'] ) || empty( $_POST['field_type'] ) ) {
			gravityview()->log->error( 'Required fields were not set in the $_POST request. ' );
			$this->_exit( false );
		}

		// Fix apostrophes added by JSON response
		$_post = array_map( 'stripslashes_deep', $_POST );

		// Sanitize
		$_post = array_map( 'esc_attr', $_post );

		// The GF type of field: `product`, `name`, `creditcard`, `id`, `text`
		$input_type = isset($_post['input_type']) ? esc_attr( $_post['input_type'] ) : NULL;
		$context = isset($_post['context']) ? esc_attr( $_post['context'] ) : NULL;

		$form_id = empty( $_post['form_id'] ) ? null : $_post['form_id'];
		$response = GravityView_Render_Settings::render_field_options( $form_id, $_post['field_type'], $_post['template'], $_post['field_id'], $_post['field_label'], $_post['area'], $input_type, '', '', $context  );

		$response = gravityview_strip_whitespace( $response );

		$this->_exit( $response );
	}

	/**
	 * Given a View id, calculates the assigned form, and returns the form fields (only the sortable ones )
	 * AJAX callback
	 *
	 *
	 * @access public
	 * @return void
	 */
	function get_sortable_fields() {
		$this->check_ajax_nonce();

		$form = '';

		// if form id is set, use it, else, get form from preset
		if( !empty( $_POST['form_id'] ) ) {

			$form = (int) $_POST['form_id'];

		}
		// get form from preset
		elseif( !empty( $_POST['template_id'] ) ) {

			$form = GravityView_Ajax::pre_get_form_fields( $_POST['template_id'] );

		}

		$response = gravityview_get_sortable_fields( $form );

		$response = gravityview_strip_whitespace( $response );

		$this->_exit( $response );
	}

	/**
	 * Get the the form fields for a preset (no form created yet)
	 * @param  string $template_id Preset template
	 *
	 */
	static function pre_get_form_fields( $template_id = '') {

		if( empty( $template_id ) ) {
			gravityview()->log->error( 'Template ID not set.' );
			return false;
		} else {
			$form_file = apply_filters( 'gravityview_template_formxml', '', $template_id );
			if( !file_exists( $form_file )  ) {
				gravityview()->log->error( 'Importing Form Fields for preset [{template_id}]. File not found. file: {path}', array( 'template_id' => $template_id, 'path' => $form_file ) );
				return false;
			}
		}

		// Load xml parser (from GravityForms)
		if( class_exists( 'GFCommon' ) ) {
			$xml_parser = GFCommon::get_base_path() . '/xml.php';
		} else {
			$xml_parser = trailingslashit( WP_PLUGIN_DIR ) . 'gravityforms/xml.php';
		}

		if( file_exists( $xml_parser ) ) {
			require_once( $xml_parser );
		} else {
			gravityview()->log->debug( ' - Gravity Forms XML Parser not found {path}.', array( 'path' => $xml_parser ) );
			return false;
		}

		// load file
		$xmlstr = file_get_contents( $form_file );

        $options = array(
            "page" => array("unserialize_as_array" => true),
            "form"=> array("unserialize_as_array" => true),
            "field"=> array("unserialize_as_array" => true),
            "rule"=> array("unserialize_as_array" => true),
            "choice"=> array("unserialize_as_array" => true),
            "input"=> array("unserialize_as_array" => true),
            "routing_item"=> array("unserialize_as_array" => true),
            "creditCard"=> array("unserialize_as_array" => true),
            "routin"=> array("unserialize_as_array" => true),
            "confirmation" => array("unserialize_as_array" => true),
            "notification" => array("unserialize_as_array" => true)
        );

		$xml = new RGXML($options);
        $forms = $xml->unserialize($xmlstr);

        if( !$forms ) {
        	gravityview()->log->error( 'Importing Form Fields for preset [{template_id}]. Error importing file. (File) {path}', array( 'template_id' => $template_id, 'path' => $form_file ) );
        	return false;
        }

        if( !empty( $forms[0] ) && is_array( $forms[0] ) ) {
        	$form = $forms[0];
        }

        if( empty( $form ) ) {
        	gravityview()->log->error( '$form not set.', array( 'data' => $forms ) );
        	return false;
        }

        gravityview()->log->debug( '[pre_get_available_fields] Importing Form Fields for preset [{template_id}]. (Form)', array( 'template_id' => $template_id, 'data' => $form ) );

        return $form;

	}


	/**
	 * Import fields configuration from an exported WordPress View preset
	 * @param  string $file path to file
	 * @return array       Fields config array (unserialized)
	 */
	function import_fields( $file ) {

		if( empty( $file ) || !file_exists(  $file ) ) {
			gravityview()->log->error( 'Importing Preset Fields. File not found. (File) {path}', array( 'path' => $file ) );
			return false;
		}

		if( !class_exists('WXR_Parser') ) {
			include_once GRAVITYVIEW_DIR . 'includes/lib/xml-parsers/parsers.php';
		}

		$parser = new WXR_Parser();
		$presets = $parser->parse( $file );

		if(is_wp_error( $presets )) {
			gravityview()->log->error( 'Importing Preset Fields failed. Threw WP_Error.', array( 'data' => $presets ) );
			return false;
		}

		if( empty( $presets['posts'][0]['postmeta'] ) && !is_array( $presets['posts'][0]['postmeta'] ) ) {
			gravityview()->log->error( 'Importing Preset Fields failed. Meta not found in file. {path}', array( 'path' => $file ) );
			return false;
		}

		gravityview()->log->debug( '[import_fields] postmeta', array( 'data' => $presets['posts'][0]['postmeta'] ) );

		$fields = $widgets = array();
		foreach( $presets['posts'][0]['postmeta'] as $meta ) {
			switch ($meta['key']) {
				case '_gravityview_directory_fields':
					$fields = maybe_unserialize( $meta['value'] );
					break;
				case '_gravityview_directory_widgets':
					$widgets = maybe_unserialize( $meta['value'] );
					break;
			}
		}

		gravityview()->log->debug( '[import_fields] Imported Preset (Fields)', array( 'data' => $fields ) );
		gravityview()->log->debug( '[import_fields] Imported Preset (Widgets)', array( 'data' => $widgets ) );

		return array(
			'fields' => $fields,
			'widgets' => $widgets
		);
	}
}

new GravityView_Ajax;
