<?php

class GravityView_View_Data {

	static $instance = NULL;

	protected $views = array();

	function __construct( $passed_post = NULL ) {

		if( !empty( $passed_post ) ) {

			$id = NULL;

			// Convert WP_Posts into array
			if( $passed_post instanceof WP_Post ) {
				$passed_post = array( $passed_post);
			}

			if( is_array( $passed_post ) ) {

				foreach ( $passed_post as &$post) {
					if( ( get_post_type( $post ) === 'gravityview' ) ) {
						$id = $post->ID;
					} else{
						$this->parse_post_content( $post->post_content );
					}
				}

			} elseif( is_string( $passed_post ) ) {
				$this->parse_post_content( $passed_post );
			} else {
				$id = $this->get_id_from_atts( $passed_post );
			}

			if( !empty( $id ) ) {
				$this->add_view( $id );
			}
		}

		self::$instance = &$this;
	}

	static function getInstance() {

		if( empty( self::$instance ) ) {
			self::$instance = new GravityView_View_Data;
		}

		return self::$instance;
	}

	function get_views() {
		return $this->views;
	}

	function get_view( $view_id, $atts = NULL ) {

		if( !is_numeric( $view_id) ) {
			do_action('gravityview_log_error', sprintf('GravityView_View_Data[get_view] $view_id passed is not numeric.', $view_id) );
			return false;
		}

		// Backup: the view hasn't been fetched yet. Doing it now.
		if ( !isset( $this->views[ $view_id ] ) ) {
			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[get_view] View #%s not set yet.', $view_id) );
			return $this->add_view( $view_id, $atts );
		}

		if ( empty( $this->views[ $view_id ] ) ) {
			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[get_view] Returning; View #%s was empty.', $view_id) );
			return false;
		}

		return $this->views[ $view_id ];
	}

	/**
	 * Determines if a post, identified by the specified ID, exist
	 * within the WordPress database.
	 *
	 * @link http://tommcfarlin.com/wordpress-post-exists-by-id/
	 * @param    int    $id    The ID of the post to check
	 * @return   bool          True if the post exists; otherwise, false.
	 * @since    1.0.0
	 */
	function view_exists( $view_id ) {
		return is_string( get_post_status( $view_id ) );
	}

	/**
	 *
	 * Add a view to the views array
	 *
	 * @param type $view_id
	 * @param type $atts Combine other attributes (eg. from shortcode) with the view settings (optional)
	 * @return type
	 */
	function add_view( $view_id, $atts = NULL ) {

		// The view has been set already; returning stored view.
		if ( !empty( $this->views[ $view_id ] ) ) {
			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[add_view] Returning; View #%s already exists.', $view_id) );

			return $this->views[ $view_id ];
		}

		if( !$this->view_exists( $view_id ) ) {
			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[add_view] Returning; View #%s does not exist.', $view_id) );
			return false;
		}

		$form_id = gravityview_get_form_id( $view_id );

		if( empty( $form_id ) ) {

			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[add_view] Returning; Post ID #%s does not have a connected form.', $view_id) );

			return false;
		}

		// Get the settings for the View ID
		$view_settings = gravityview_get_template_settings( $view_id );

		do_action('gravityview_log_debug', sprintf('GravityView_View_Data[add_view] Settings pulled in from View #%s', $view_id), $view_settings );

		// Merge the view settings with the defaults
		$view_defaults = wp_parse_args( $view_settings, self::get_default_args() );

		do_action('gravityview_log_debug', 'GravityView_View_Data[add_view] View Defaults after merging View Settings with the default args.', $view_defaults );

		if( !empty( $atts ) && is_array( $atts ) ) {

			do_action('gravityview_log_debug', 'GravityView_View_Data[add_view] $atts before merging  with the $view_defaults', $atts );

			// Get the settings from the shortcode and merge them with defaults.
			$atts = shortcode_atts( $view_defaults, $atts );

			do_action('gravityview_log_debug', 'GravityView_View_Data[add_view] $atts after merging  with the $view_defaults', $atts );

		} else {

			// If there are no passed $atts, the defaults will be used.
			$atts = $view_defaults;

		}

		unset( $atts['id'], $view_defaults, $view_settings );

		$data = array(
			'id' => $view_id,
			'view_id' => $view_id,
			'form_id' => $form_id,
			'template_id' => gravityview_get_template_id( $view_id ),
			'atts' => $atts,
			'fields' => $this->get_fields( $view_id ),
			'widgets' => get_post_meta( $view_id, '_gravityview_directory_widgets', true ),
			'form' => gravityview_get_form( $form_id ),
		);

		do_action('gravityview_log_debug', sprintf('GravityView_View_Data[add_view] View #%s being added.', $view_id), $data );

		$this->views[ $view_id ] = $data;

		return $this->views[ $view_id ];
	}

	/**
	 * Get the visible fields for a View
	 * @uses  gravityview_get_directory_fields() Fetch the configured fields for a View
	 * @uses  GravityView_View_Data::filter_fields() Only show visible fields
	 * @param  int $view_id View ID
	 * @return array          Array of fields as passed by `gravityview_get_directory_fields()`
	 */
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

			foreach( (array)$fields as $uniqid => $properties ) {

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

	function get_id_from_atts( $atts ) {

		$atts = is_array( $atts ) ? $atts : shortcode_parse_atts( $atts );

		// Get the settings from the shortcode and merge them with defaults.
		$atts = wp_parse_args( $atts, self::get_default_args() );

		$view_id = !empty( $atts['view_id'] ) ? (int)$atts['view_id'] : NULL;

		if( empty( $view_id ) && !empty( $atts['id'] ) ) {
			$view_id = (int)$atts['id'];
		}

		if( empty( $view_id ) ) {
			do_action('gravityview_log_error', 'GravityView_View_Data[get_id_from_atts] Returning; no ID defined (Atts)', $atts );
			return;
		}

		return $view_id;
	}

	/**
	 * Parse content to determine if there is a GV shortcode to allow for enqueing necessary files in the head.
	 *
	 * @uses gravityview_has_shortcode_r() Check whether shortcode exists (recursively)
	 * @uses shortcode_parse_atts() Parse each GV shortcode
	 * @uses  gravityview_get_template_settings() Get the settings for the View ID
	 * @param  string $content $post->post_content content
	 * @return void
	 */
	function parse_post_content( $content ) {

		$shortcodes = gravityview_has_shortcode_r( $content, 'gravityview' );

		if( empty( $shortcodes ) ) { return array(); }

		do_action('gravityview_log_debug', 'GravityView_View_Data[parse_post_content] Parsing content, found shortcodes:', $shortcodes );

		foreach ($shortcodes as $key => $shortcode) {

			$args = shortcode_parse_atts( $shortcode[3] );

			if( empty( $args['id'] ) ) {
				do_action('gravityview_log_error', sprintf( 'GravityView_View_Data[parse_post_content] Returning; no ID defined in shortcode atts for Post #%s (Atts)', $post->ID ), $shortcode );
				return false;
			}

			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[parse_post_content] Adding view #%s with shortcode args', $args['id']), $args );

			// Store the View to the object for later fetching.
			$this->add_view( $args['id'] , $args );
		}

	}

	/**
	 * Get a specific default setting
	 * @param  string  $key          The key of the setting array item
	 * @param  boolean $with_details Include details
	 * @return mixed|array                If using $with_details, return array. Otherwise, mixed.
	 */
	public static function get_default_arg( $key, $with_details = false ) {

		$args = self::get_default_args( $with_details );

		if( !isset( $args[ $key ] ) ) { return NULL; }

		return $args[ $key ];
	}

	/**
	 * Retrieve the default args for shortcode and theme function
	 *
	 * @param boolean $with_details True: Return array with full default settings information, including description, name, etc. False: Return an array with only key => value pairs.
	 * @param string $group Only fetch
	 * @access public
	 * @static
	 * @return void
	 * @filter gravityview_default_args Modify the default settings for new Views
	 */
	public static function get_default_args( $with_details = false, $group = NULL ) {

		$default_settings = apply_filters( 'gravityview_default_args', array(
			'id' => array(
				'label' => __('View ID', 'gravityview'),
				'type' => 'number',
				'group'	=> 'default',
				'value' => NULL,
				'tooltip' => NULL,
				'show_in_shortcode' => false,
			),
			'page_size' => array(
				'label' 	=> __('Number of entries per page', 'gravityview'),
				'type' => 'number',
				'class'	=> 'small-text',
				'group'	=> 'default',
				'value' => 25,
				'show_in_shortcode' => true,
			),
			'lightbox' => array(
				'label' => __( 'Enable lightbox for images', 'gravityview' ),
				'type' => 'checkbox',
				'group'	=> 'default',
				'value' => 1,
				'tooltip' => NULL,
				'show_in_shortcode' => true,
			),
			'show_only_approved' => array(
				'label' => __( 'Show only approved entries', 'gravityview' ),
				'type' => 'checkbox',
				'group'	=> 'default',
				'value' => 0,
				'show_in_shortcode' => false,
			),
			'hide_empty' => array(
				'label' 	=> __( 'Hide empty fields', 'gravityview' ),
				'group'	=> 'default',
				'type'	=> 'checkbox',
				'value' => 1,
				'show_in_shortcode' => false,
			),
			'user_edit' => array(
				'label'	=> __( 'Allow User Edit', 'gravityview' ),
				'group'	=> 'default',
				'desc'	=> __('Allow logged-in users to edit entries they created.', 'gravityview'),
				'value'	=> 0,
				'tooltip' => __('Display "Edit Entry" fields to non-administrator users if they created the entry. Edit Entry fields will always be displayed to site administrators.', 'gravityview'),
				'type'	=> 'checkbox',
				'show_in_shortcode' => true,
			),
			'user_delete' => array(
				'label'	=> __( 'Allow User Delete', 'gravityview' ),
				'group'	=> 'default',
				'desc'	=> __('Allow logged-in users to delete entries they created.', 'gravityview'),
				'value'	=> 0,
				'tooltip' => __('Display "Delete Entry" fields to non-administrator users if they created the entry. Delete Entry fields will always be displayed to site administrators.', 'gravityview'),
				'type'	=> 'checkbox',
				'show_in_shortcode' => true,
			),
			'sort_field' => array(
				'label'	=> __('Sort by field', 'gravityview'),
				'type' => 'select',
				'value' => '',
				'group'	=> 'sort',
				'options' => array(
					'' => __( 'Default', 'gravityview'),
					'date_created' => __( 'Date Created', 'gravityview'),
				),
				'show_in_shortcode' => true,
			),
			'sort_direction' => array(
				'label' 	=> __('Sort direction', 'gravityview'),
				'type' => 'select',
				'value' => 'ASC',
				'group'	=> 'sort',
				'options' => array(
					'ASC' => __('ASC', 'gravityview'),
					'DESC' => __('DESC', 'gravityview'),
				),
				'show_in_shortcode' => true,
			),
			'start_date' => array(
				'label' 	=> __('Filter by Start Date', 'gravityview'),
				'class'	=> 'gv-datepicker',
				'desc'	=> __('Show entries submitted after this date. Supports relative dates, such as "-1 week" or "-1 month".', 'gravityview' ),
				'type' => 'text',
				'value' => '',
				'group'	=> 'filter',
				'show_in_shortcode' => true,
			),
			'end_date' => array(
				'label' 	=> __('Filter by End Date', 'gravityview'),
				'class'	=> 'gv-datepicker',
				'desc'	=> __('Show entries submitted before this date. Supports relative dates, such as "now" or "-3 days".', 'gravityview' ),
				'type' => 'text',
				'value' => '',
				'group'	=> 'filter',
				'show_in_shortcode' => true,
			),
			'class' => array(
				'label' 	=> __('CSS Class', 'gravityview'),
				'desc'	=> __('CSS class to add to the wrapping HTML container.', 'gravityview'),
				'group'	=> 'default',
				'type' => 'text',
				'value' => '',
				'show_in_shortcode' => false,
			),
			'search_value' => array(
				'label' 	=> __('Search Value', 'gravityview'),
				'desc'	=> __('Define a default search value for the View', 'gravityview'),
				'type' => 'text',
				'value' => '',
				'group'	=> 'filter',
				'show_in_shortcode' => false,
			),
			'search_field' => array(
				'label' 	=> __('Search Field', 'gravityview'),
				'desc'	=> __('If Search Value is set, you can define a specific field to search in. Otherwise, all fields will be searched.', 'gravityview'),
				'type' => 'number',
				'value' => '',
				'group'	=> 'filter',
				'show_in_shortcode' => false,
			),
			'single_title' => array(
				'label'	=> __('Single Entry Title', 'gravityview'),
				'type'	=> 'text',
				'desc'	=> __('When viewing a single entry, change the title of the page to this setting. Otherwise, the title will not change between the Multiple Entries and Single Entry views.', 'gravityview'),
				'group'	=> 'default',
				'value'	=> '',
				'show_in_shortcode' => false,
				'full_width' => true,
			),
			'back_link_label' => array(
				'label'	=> __('Back Link Label', 'gravityview'),
				'group'	=> 'default',
				'desc'	=> __('The text of the link that returns to the multiple entries view.', 'gravityview'),
				'type'	=> 'text',
				'value'	=> '',
				'show_in_shortcode' => false,
				'full_width' => true,
			),
		));

		// By default, we only want the key => value pairing, not the whole array.
		if( empty( $with_details ) ) {

			$defaults = array();

			foreach( $default_settings as $key => $value ) {
				$defaults[ $key ] = $value['value'];
			}

			return $defaults;

		}
		// But sometimes, we want all the details.
		else {

			foreach ($default_settings as $key => $value) {

				// If the $group argument is set for the method,
				// ignore any settings that aren't in that group.
				if( !empty( $group ) && is_string( $group ) ) {
					if( empty( $value['group'] ) || $value['group'] !== $group ) {
						unset( $default_settings[ $key ] );
					}
				}

			}

			return $default_settings;

		}
	}

	static function shortcode_atts( $atts ) {

		do_action( 'gravityview_log_debug', 'GravityView_View_Data[shortcode_atts] Init Shortcode. Attributes: ',  $atts );

		//confront attributes with defaults
		$args = shortcode_atts( self::get_default_args() , $atts, 'gravityview' );

		do_action( 'gravityview_log_debug', 'GravityView_View_Data[shortcode_atts] Init Shortcode. Merged Attributes: ', $args );

	}

}
