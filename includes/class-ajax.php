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

	/** -------- AJAX ---------- */

	/**
	 * Verify the nonce. Exit if not verified.
	 * @return void
	 */
	function check_ajax_nonce() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxviews' ) ) {
			exit( false );
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

		// If Form was changed, JS sends form ID, if start fresh, JS sends template_id
		if( !empty( $_POST['form_id'] ) ) {
			do_action( 'gravityview_render_available_fields', (int) $_POST['form_id'] );
			exit();
		} elseif( !empty( $_POST['template_id'] ) ) {
			$form = GravityView_Ajax::pre_get_form_fields( $_POST['template_id'] );
			do_action( 'gravityview_render_available_fields', $form );
			exit();
		}

		//if everything fails..
		exit( false );
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
			exit( false );
		}

		ob_start();
		do_action( 'gravityview_render_directory_active_areas', $_POST['template_id'], 'directory', '', true );
		$response['directory'] = ob_get_clean();

		ob_start();
		do_action( 'gravityview_render_directory_active_areas',  $_POST['template_id'], 'single', '', true );
		$response['single'] = ob_get_clean();

		exit( json_encode( $response ) );
	}

	/**
	 * Fill in active areas with preset configuration according to the template selected
	 * @return void
	 */
	function get_preset_fields_config() {

		$this->check_ajax_nonce();

		if( empty( $_POST['template_id'] ) ) {
			exit( false );
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
		$template_areas = apply_filters( 'gravityview_template_active_areas', array(), $template_id );

		// widget areas
		$default_widget_areas = GravityView_Plugin::get_default_widget_areas();

		ob_start();
		do_action('gravityview_render_active_areas', $template_id, 'widget', 'header', $default_widget_areas, $presets['widgets'] );
		$response['header'] = ob_get_contents();
		ob_end_clean();

		ob_start();
		do_action('gravityview_render_active_areas', $template_id, 'widget', 'footer', $default_widget_areas, $presets['widgets'] );
		$response['footer'] = ob_get_contents();
		ob_end_clean();

		ob_start();
		do_action('gravityview_render_active_areas', $template_id, 'field', 'directory', $template_areas, $presets['fields'] );
		$response['directory'] = ob_get_contents();
		ob_end_clean();

		ob_start();
		do_action('gravityview_render_active_areas', $template_id, 'field', 'single', $template_areas, $presets['fields'] );
		$response['single'] = ob_get_contents();
		ob_end_clean();

		do_action( 'gravityview_log_debug', '[get_preset_fields_config] AJAX Response', $response );

		exit( json_encode( $response ) );
	}

	/**
	 * Create the preset form requested before the View save
	 *
	 * @return void
	 */
	function create_preset_form() {

		$this->check_ajax_nonce();

		if( empty( $_POST['template_id'] ) ) {
			do_action( 'gravityview_log_error', '[create_preset_form] Cannot create preset form; the template_id is empty.' );
			exit( false );
		}

		// get the xml for this specific template_id
		$preset_form_xml_path = apply_filters( 'gravityview_template_formxml', '', $_POST['template_id'] );

		// import form
		$form_id = $this->import_form( $preset_form_xml_path );

		// get the form ID
		if( $form_id === false ) {
			// send error to user
			do_action( 'gravityview_log_error', '[create_preset_form] Error importing form for template id: ' . (int) $_POST['template_id'] );

			exit( false );
		}

		echo '<option value="'.$form_id.'" selected></option>';

		exit();

	}

	/**
	 * Import Gravity Form XML
	 * @param  string $xml_path Path to form xml file
	 * @return int | bool       Imported form ID or false
	 */
	function import_form( $xml_path = '' ) {

		do_action( 'gravityview_log_debug', '[import_form] Import Preset Form. (File)', $xml_path );

		if( empty( $xml_path ) || !class_exists('GFExport') || !file_exists( $xml_path ) ) {
			do_action( 'gravityview_log_error', '[import_form] Class GFExport or file not found. file: ' , $xml_path );
			return false;
		}

		// import form
		$forms = '';
		$count = GFExport::import_file( $xml_path, $forms );

		do_action( 'gravityview_log_debug', '[import_form] Importing form (Result)', $count );
		do_action( 'gravityview_log_debug', '[import_form] Importing form (Form) ', $forms );

		if( $count != 1 || empty( $forms[0]['id'] ) ) {
			do_action( 'gravityview_log_error', '[import_form] Form Import Failed!' );
			return false;
		}

		// import success - return form id
		return $forms[0]['id'];
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
			do_action( 'gravityview_log_error', '[get_field_options] Required fields were not set in the $_POST request. ' );
			exit( false );
		}

		// Fix apostrophes added by JSON response
		$post = array_map( 'stripslashes_deep', $_POST );

		// Sanitize
		$post = array_map( 'esc_attr', $post );

		// The GF type of field: `product`, `name`, `creditcard`, `id`, `text`
		$input_type = isset($post['input_type']) ? esc_attr( $post['input_type'] ) : NULL;
		$context = isset($post['context']) ? esc_attr( $post['context'] ) : NULL;

		$response = GravityView_Render_Settings::render_field_options( $post['field_type'], $post['template'], $post['field_id'], $post['field_label'], $post['area'], $input_type, '', '', $context  );

		exit( $response );
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

		} elseif( !empty( $_POST['template_id'] ) ) {

			$form = GravityView_Ajax::pre_get_form_fields( $_POST['template_id'] );

		}

		$response = gravityview_get_sortable_fields( $form );

		exit( $response );
	}

	/**
	 * Get the the form fields for a preset (no form created yet)
	 * @param  string $template_id Preset template
	 *
	 */
	static function pre_get_form_fields( $template_id = '') {

		if( empty( $template_id ) ) {
			return false;
		} else {
			$form_file = apply_filters( 'gravityview_template_formxml', '', $template_id );
			if( !file_exists( $form_file )  ) {
				do_action( 'gravityview_log_error', '[pre_get_available_fields] Importing Form Fields for preset ['. $template_id .']. File not found. file: ' . $form_file );
				return false;
			}
		}

		// Load xml parser (from GravityForms)
		$xml_parser = trailingslashit( WP_PLUGIN_DIR ) . 'gravityforms/xml.php';
		if( file_exists( $xml_parser ) ) {
			require_once( $xml_parser );
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
        	do_action( 'gravityview_log_error', '[pre_get_available_fields] Importing Form Fields for preset ['. $template_id .']. Error importing file. (File)', $form_file );
        	return false;
        }

        if( !empty( $forms[0] ) && is_array( $forms[0] ) ) {
        	$form = $forms[0];
        }

        do_action( 'gravityview_log_debug', '[pre_get_available_fields] Importing Form Fields for preset ['. $template_id .']. (Form)', $form );

        return $form;

	}


	/**
	 * Import fields configuration from an exported WordPress View preset
	 * @param  string $file path to file
	 * @return array       Fields config array (unserialized)
	 */
	function import_fields( $file ) {

		if( empty( $file ) || !file_exists(  $file ) ) {
			do_action( 'gravityview_log_error', '[import_fields] Importing Preset Fields. File not found. (File)', $file );
			return false;
		}

		if( !class_exists('WXR_Parser') ) {
			include_once GRAVITYVIEW_DIR . 'includes/lib/xml-parsers/parsers.php';
		}

		$parser = new WXR_Parser();
		$presets = $parser->parse( $file );

		if(is_wp_error( $presets )) {
			do_action( 'gravityview_log_error', '[import_fields] Importing Preset Fields failed. Threw WP_Error.', $presets );
			return false;
		}

		if( empty( $presets['posts'][0]['postmeta'] ) && !is_array( $presets['posts'][0]['postmeta'] ) ) {
			do_action( 'gravityview_log_error', '[import_fields] Importing Preset Fields failed. Meta not found in file.', $file );
			return false;
		}

		do_action( 'gravityview_log_debug', '[import_fields] postmeta', $presets['posts'][0]['postmeta'] );

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

		do_action( 'gravityview_log_debug', '[import_fields] Imported Preset (Fields)', $fields );
		do_action( 'gravityview_log_debug', '[import_fields] Imported Preset (Widgets)', $widgets );

		return array(
			'fields' => $fields,
			'widgets' => $widgets
		);
	}
}

new GravityView_Ajax;
