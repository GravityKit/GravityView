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
}

new GravityView_Ajax;
