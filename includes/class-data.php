<?php

class GravityView_Output {

	static $instance = NULL;
	var $views = array();
	var $template_id = NULL;
	var $settings = array();

	function __construct( $atts_array_or_string = NULL ) {

		if( !empty( $atts_array_or_string ) ) {
			if( is_string( $atts_array_or_string ) ) {
				$this->parse_post_content( $atts_array_or_string );
			} else {
				$this->parse_atts( $atts_array_or_string );
			}
		}
	}

	function getInstance() {

		if( !empty( self::$instance ) ) {
			return self::$instance;
		} else {
			return new GravityView_Output;
		}
	}

	function get_view( $view_id ) {

		if ( empty( $this->views[ $view_id ] ) ) {
			do_action('gravityview_log_debug', sprintf('GravityView_Output[get_view] Returning; View #%s does not exist.', $view_id) );
		}

		return $this->views[ $view_id ];

	}

	function add_view( $view_id ) {

		if ( !empty( $this->views[ $view_id ] ) ) {
			do_action('gravityview_log_debug', sprintf('GravityView_Output[add_view] Returning; View #%s already exists.', $view_id) );

			return $this->views[ $view_id ];
		}

		$data = new GravityView_Data( array(
			'id' => $view_id,
			'form_id' => gravityview_get_form_id( $view_id ),
			'template_id' => gravityview_get_template_id( $view_id ),
			'atts' => gravityview_get_template_settings( $view_id ),
			'fields' => $this->get_fields( $view_id ),
		));

		$this->views[ $view_id ] = $data;

		return $data;
	}

	function get_fields( $view_id ) {

		$dir_fields = gravityview_get_directory_fields( $view_id );
		do_action( 'gravityview_log_debug', '[render_view] Fields: ', $dir_fields );

		// remove fields according to visitor visibility permissions (if logged-in)
		$dir_fields = $this->filter_fields( $dir_fields );
		do_action( 'gravityview_log_debug', '[render_view] Fields after visibility filter: ', $dir_fields );

		return $dir_fields;
	}

	/**
	 * Filter area fields based on specified conditions
	 *
	 * @access public
	 * @param array $dir_fields
	 * @return void
	 */
	private function filter_fields( $dir_fields ) {

		if( empty( $dir_fields ) || !is_array( $dir_fields ) ) {
			return $dir_fields;
		}

		foreach( $dir_fields as $area => $fields ) {
			foreach( $fields as $uniqid => $properties ) {

				if( $this->hide_field_check_conditions( $properties ) ) {
					unset( $dir_fields[ $area ][ $uniqid ] );
				}

			}
		}

		return $dir_fields;

	}


	/**
	 * Check wether a certain field should not be presented based on its own properties.
	 *
	 * @access public
	 * @param array $properties
	 * @return true (field should be hidden) or false (field should be presented)
	 */
	private function hide_field_check_conditions( $properties ) {

		// logged-in visibility
		if( !empty( $properties['only_loggedin'] ) && !current_user_can( $properties['only_loggedin_cap'] ) ) {
			return true;
		}

		return false;
	}

	function parse_atts( $atts ) {

		$atts = is_array( $atts ) ? $atts : shortcode_parse_atts( $atts );

		// Get the settings from the shortcode and merge them with defaults.
		$atts = wp_parse_args( $atts, self::get_default_args() );

		$view_id = !empty( $atts['id'] ) ? $atts['id'] : NULL;

		if( empty( $atts['id'] ) ) {
			do_action('gravityview_log_error', 'GravityView_Output[parse_atts] Returning; no ID defined (Atts)', $atts );
			return;
		}

		return $this->add_view( $atts['id'] );
	}

	function parse_post_content( $content ) {

		$shortcodes = gravityview_has_shortcode_r( $content, 'gravityview' );

		if( empty( $shortcodes ) ) { return array(); }

		foreach ($shortcodes as $key => $shortcode) {

			// Get the settings from the shortcode and merge them with defaults.
			$shortcode_atts = wp_parse_args( shortcode_parse_atts( $shortcode[3] ), self::get_default_args() );

			if( empty( $shortcode_atts['id'] ) ) {
				do_action('gravityview_log_error', sprintf( '[gravityview_get_view_meta] Returning; no ID defined in shortcode atts for Post #%s (Atts)', $post->ID ), $shortcode_atts );
				return false;
			}

			$this->add_view( $shortcode_atts['id'] );
		}

	}

	private function r( $content = '', $die = false, $title ='') {
		if( !empty($title)) { echo "<h3>{$title}</h3>"; }
		echo '<pre>'; print_r($content); echo '</pre>';
		if($die) { die(); }
	}

	/**
	 * Retrieve the default args for shortcode and theme function
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function get_default_args() {

		$defaults = array(
			'id' => NULL,
			'lightbox' => true,
			'page_size' => NULL,
			'sort_field' => NULL,
			'sort_direction' => 'ASC',
			'start_date' => NULL,
			'end_date' => 'now',
			'class' => NULL,
			'search_value' => NULL,
			'search_field' => NULL,
			'single_title' => NULL,
			'back_link_label' => NULL,
			'hide_empty' => true,
		);

		return $defaults;
	}

	static function shortcode_atts( $atts ) {

		do_action( 'gravityview_log_debug', 'GravityView_Output[shortcode_atts] Init Shortcode. Attributes: ',  $atts );

		//confront attributes with defaults
		$args = shortcode_atts( self::get_default_args() , $atts, 'gravityview' );

		do_action( 'gravityview_log_debug', 'GravityView_Output[shortcode_atts] Init Shortcode. Merged Attributes: ', $args );

	}

}

class GravityView_Data {

	var $form_id;

	var $form;

	var $view_id;

	var $fields;

	var $context;

	var $post_id;

	/**
	 * Construct the view object
	 * @param  array       $atts Associative array to set the data of
	 */
	function __construct( $atts = array() ) {

		$atts = wp_parse_args( $atts, array(
			'form_id' => NULL,
			'view_id' => NULL,
			'fields'  => NULL,
			'context' => NULL,
			'post_id' => NULL,
		) );

		// store form if not defined yet
		if( !array_key_exists( 'form', $atts ) && !empty( $atts['form_id'] ) ) {
			$atts['form'] = gravityview_get_form( $atts['form_id'] );
		}

		foreach ($atts as $key => $value) {
			$this->{$key} = $value;
		}

	}

	function fill( $view_id ) {
		$this->view_id = $view_id;
		$this->form_id = gravityview_get_form_id( $view_id );
		$output['template_id'] = gravityview_get_template_id( $view_id );
		$this->atts = gravityview_get_template_settings( $view_id );
	}

	function setEntries( $entries ) {
		$this->entries = $entries;
	}

}
